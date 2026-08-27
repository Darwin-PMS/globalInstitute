<?php

namespace App\Domains\OnlineLearning\Room\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The scheduled thing a room belongs to.
 *
 * THIS IS THE EXTRACTION SEAM. The room layer never touches OnlineClass,
 * Enrollment, Student or Trainer directly — it only asks a subject these
 * questions. To run the Live Class Room on a different backing entity (a
 * class_sessions row, or an external LMS course), implement this interface and
 * bind a resolver; nothing else in the room layer changes.
 */
interface RoomSubjectInterface
{
    /* ---------------------------------------------------------- identity */

    public function subjectType(): string;

    public function subjectId(): int|string;

    public function title(): string;

    public function description(): ?string;

    /** e.g. "AutoCAD Professional · 2D Drafting" — shown under the room title. */
    public function contextLabel(): ?string;

    /* ---------------------------------------------------------- schedule */

    public function startsAt(): Carbon;

    public function endsAt(): Carbon;

    public function timezone(): string;

    /** The subject's own status string, for display alongside the room state. */
    public function scheduleStatus(): string;

    public function isCancelled(): bool;

    public function cancellationReason(): ?string;

    /* ------------------------------------------------------------- people */

    /** The user id that should host this room, if one is assigned. */
    public function hostUserId(): ?int;

    public function hostName(): ?string;

    /**
     * Everyone expected in this room.
     *
     * @return Collection<int, array{user_id:int,name:string,reference_id:int|null,role:string}>
     */
    public function expectedRoster(): Collection;

    /** Is this user expected in the room (enrolled / hosting / privileged)? */
    public function isEnrolled(int $userId): bool;

    /* ------------------------------------------------------------ meeting */

    public function meetingProvider(): ?string;

    /**
     * The join link. Callers must not expose this until access has been
     * authorised — the room layer only hands it out through a join attempt.
     */
    public function meetingUrl(): ?string;

    public function meetingId(): ?string;

    public function meetingPassword(): ?string;

    /* ------------------------------------------------------------ content */

    /** @return Collection<int, array<string, mixed>> */
    public function materials(bool $publishedOnly = true): Collection;

    /** @return Collection<int, array<string, mixed>> */
    public function recordings(bool $publishedOnly = true): Collection;

    /** @return Collection<int, array<string, mixed>> */
    public function assignments(bool $publishedOnly = true): Collection;

    /* --------------------------------------------------------- attendance */

    /** @return Collection<int, array<string, mixed>> */
    public function attendanceRoster(): Collection;

    public function attendanceStats(): array;

    /**
     * Persist attendance for this subject.
     *
     * @param  array<int, array{user_id:int,status:string,notes:?string}>  $records
     */
    public function recordAttendance(array $records, ?int $markedByUserId = null): void;

    /** Whether attendance is expected to be tracked at all. */
    public function tracksAttendance(): bool;

    /** The underlying model, for callers that legitimately need it. */
    public function model(): mixed;
}
