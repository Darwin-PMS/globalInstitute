# Live Class Room

A self-contained virtual classroom for one scheduled class. Each room owns its
session state, participants, chat, timeline and settings, independently of every
other room.

Built to be lifted out: nothing in this directory references an institute model
except the single adapter in `Subjects/`.

---

## The seam

```
        ┌──────────────────────────────────────────────┐
        │  Room layer (this directory)                 │
        │  state · presence · chat · timeline · access  │
        └──────────────────┬───────────────────────────┘
                           │ asks only these questions
                  RoomSubjectInterface
                           │
        ┌──────────────────┴───────────────────────────┐
        │  OnlineClassSubject  (the only adapter)      │
        │  → OnlineClass, Enrollment, Student, Trainer │
        └──────────────────────────────────────────────┘
```

`class_rooms` is polymorphic (`subject_type` + `subject_id`), so a room can bind
to anything. Today that is `App\Models\OnlineClass`.

**To run this on a different backing entity**, implement
`RoomSubjectInterface` + `RoomSubjectResolverInterface` and rebind one line in
`OnlineLearningServiceProvider::registerContracts()`:

```php
$this->app->bind(RoomSubjectResolverInterface::class, YourSubjectResolver::class);
```

Nothing else in the room layer changes.

---

## Layout

| Path | Purpose |
|---|---|
| `Contracts/` | `RoomSubjectInterface`, `RoomSubjectResolverInterface` — the seam |
| `Enums/` | `RoomState`, `RoomRole`, `RoomEventType`, `RoomMessageType` |
| `Models/` | `ClassRoom`, `ClassRoomParticipant`, `ClassRoomMessage`, `ClassRoomEvent` |
| `Services/` | `ClassRoomService` (provisioning + lifecycle), `RoomAccessService`, `RoomPresenceService`, `RoomMessageService`, `RoomTimelineService` |
| `Subjects/` | `OnlineClassSubject` + resolver — **the only institute-aware files** |
| `Events/` | `RoomStateChanged`, `RoomPresenceChanged`, `RoomMessagePosted` |

Outside this directory:

- `app/Http/Controllers/Api/OnlineLearning/Room/` — HTTP layer
- `resources/js/online-learning/room/` — the room's own front end
- `resources/views/online-learning/room.blade.php` — the shell
- `config/online-learning.php` → `room` key — defaults and poll intervals

---

## Session state

A room's state is its own, not a mirror of the class status.

```
scheduled ──▶ open ──▶ live ──▶ ended ──▶ archived
     └──────────┴────────┴──▶ cancelled
```

- **open** — the lobby. Reached automatically once
  `lobby_opens_minutes_before` is hit; `syncState()` runs on every room read, so
  no scheduler is needed.
- **live** — only a host starting the class does this. Time alone never makes a
  room live, because an unattended class is not a live class.
- **ended** — the host ending it, or `auto_close_grace_minutes` after the
  scheduled end.

Illegal transitions are ignored rather than throwing, so a double-clicked
"Start" is harmless. Every transition is written to the timeline and dispatched
as an event.

### Settings are per room
Provisioning copies the defaults into the row. Changing a default later never
rewrites a class already in progress, and turning chat off in one classroom does
not affect another.

---

## Presence vs attendance

Deliberately separate:

- **Presence** (`class_room_participants`) — the raw signal. Join, heartbeat,
  leave. Re-entering accumulates time rather than starting over, so a flaky
  connection is not punished.
- **Attendance** (`class_attendance`, via the subject) — the graded record. The
  room *proposes* it from measured presence using the thresholds in
  `config('online-learning.attendance')`; a host confirms. A dropped connection
  never silently changes someone's record.

> Duration arithmetic uses `ClassRoomParticipant::elapsed()` (timestamps), not
> `diffInSeconds()`. Carbon 3 returns a **signed** difference, so argument order
> silently decided whether presence counted at all.

---

## Real-time readiness

The client subscribes to topics on `RoomChannel`; it never polls directly and
never touches a socket:

```js
channel.on('messages', ({ messages }) => append(messages));
channel.start('messages');
```

Today `RoomChannel` fills those topics by polling the delta endpoints. Every feed
takes `?after=<id>` and returns only what followed, which is the same shape a
socket would push.

**To go real-time:** implement `RoomChannel.connectRealtime()` against
Echo/Pusher, publish into `this.emit(topic, payload)` with the identical
payloads, and set `this.realtime = true` so polling stands down. Server side the
three events already carry a per-room channel name (`class-room.{id}`) and hold
ids only, so they are safe to broadcast as-is — add `ShouldBroadcast` and a
`broadcastOn()`. No panel changes.

---

## Lazy loading

The shell downloads the stage and nothing else. Each panel is a dynamic
`import()`, so Vite emits it as its own chunk and it arrives the first time its
tab is opened:

```
app (room shell)  23.1 kB
├── stage          9.2 kB
├── chat           8.1 kB
├── attendance     5.8 kB
├── roster         4.3 kB
├── resources      4.2 kB   (materials · recordings · assignments)
└── activity       2.3 kB
```

Panels are destroyed on tab switch, which stops their channel subscriptions and
timers. Requests are de-duplicated and superseded ones aborted per key, and
polling pauses while the tab is hidden.

---

## Security

Every endpoint resolves the actor from the session and re-derives their role for
*that room*; nothing trusts the client. The capability map returned to the front
end decides what to **draw**, never what is **permitted**.

- The meeting URL is never in the bootstrap payload. It is released only by
  `POST /enter`, after membership and the join window are checked, and the
  attempt is recorded.
- Host credentials (`meeting_id`, `meeting_password`) go to hosts only.
- Participants get a presence *count*; only hosts get the name list.
- Rooms are addressed by an opaque `room_key` (uuid), so a room URL cannot be
  walked to reach another class.

---

## API

`/api/online-learning/`

| Method | Path | Notes |
|---|---|---|
| GET | `classes/{onlineClass}/room` | Resolve + provision; returns the room key |
| GET | `rooms/{room}` | Bootstrap: identity, state, capabilities, panel list |
| GET | `rooms/{room}/state` | Cheap poll |
| POST | `rooms/{room}/enter` \| `heartbeat` \| `leave` | Presence |
| POST | `rooms/{room}/start` \| `end` | Host only |
| PUT | `rooms/{room}/settings` | Host only |
| GET | `rooms/{room}/{materials\|recordings\|assignments}` | Lazy panels |
| GET | `rooms/{room}/{roster\|presence}` | Roster is host only |
| GET/POST | `rooms/{room}/attendance` | Host only; GET includes the proposal |
| GET | `rooms/{room}/activity?after=` | Delta feed |
| GET/POST | `rooms/{room}/messages?after=` | Delta feed |
| POST/DELETE | `rooms/{room}/messages/{message}[/pin]` | Host only |

All responses use the module envelope `{ success, message, data, meta }`.

Shell: `GET /learning/room/{roomKey}` — one URL for every role.

---

## Tests

`tests/Feature/OnlineLearning/Room/` — 41 tests covering provisioning and room
independence, the state machine, role resolution, entry authorisation and link
disclosure, presence accumulation, the attendance proposal, per-room setting
isolation, panel authorisation, and the delta feeds.
