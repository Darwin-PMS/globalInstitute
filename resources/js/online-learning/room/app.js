/**
 * Live Class Room entry point.
 *
 * Its own Vite bundle, separate from the Online Learning SPA, so the room can be
 * lifted out as a product without untangling shared entry code.
 */
import { RoomApp } from './RoomApp.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = window.ClassRoomConfig;

    if (!config?.roomKey) {
        console.error('[ClassRoom] No room key was published by the shell.');
        return;
    }

    window.classRoom = new RoomApp(config);
    window.classRoom.boot();
});
