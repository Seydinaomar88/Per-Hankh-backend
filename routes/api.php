<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\AuthController;

use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\BoardController;
use App\Http\Controllers\Api\V1\KanbanColumnController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\NoteController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\FileController;

Route::prefix('v1')->group(function () {

    /**
     * =========================================================
     * PUBLIC AUTH
     * =========================================================
     */

    Route::post(
        '/register',
        [AuthController::class, 'register']
    );

    Route::post(
        '/login',
        [AuthController::class, 'login']
    );

    /**
     * =========================================================
     * PROTECTED ROUTES
     * =========================================================
     */

    Route::middleware('auth:sanctum')->group(function () {

        /**
         * =====================================================
         * AUTH USER
         * =====================================================
         */

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
            '/me',
            [AuthController::class, 'me']
        );

        /**
         * =====================================================
         * WORKSPACES
         * =====================================================
         */

        Route::get(
            '/workspaces',
            [WorkspaceController::class, 'index']
        );

        Route::post(
            '/workspaces',
            [WorkspaceController::class, 'store']
        );

        /**
         * =====================================================
         * NOTIFICATIONS
         * =====================================================
         */

        Route::get(
            '/notifications',
            [NotificationController::class, 'index']
        );

        Route::get(
            '/notifications/unread',
            [NotificationController::class, 'unread']
        );

        Route::get(
            '/notifications/unread-count',
            [NotificationController::class, 'unreadCount']
        );

        Route::patch(
            '/notifications/{notification}/read',
            [NotificationController::class, 'markAsRead']
        );

        Route::patch(
            '/notifications/read-all',
            [NotificationController::class, 'markAllAsRead']
        );

        Route::delete(
            '/notifications/{notification}',
            [NotificationController::class, 'destroy']
        );

        /**
         * =====================================================
         * WORKSPACE ACCESS
         * =====================================================
         */

        Route::middleware('workspace.access')->group(function () {

            /**
             * =================================================
             * SINGLE WORKSPACE
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}',
                [WorkspaceController::class, 'show']
            );

            /**
             * =================================================
             * BOARDS
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/boards',
                [BoardController::class, 'index']
            );

            Route::get(
                '/workspaces/{workspace}/boards/{board}',
                [BoardController::class, 'show']
            );

            /**
             * =================================================
             * COLUMNS
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/boards/{board}/columns',
                [KanbanColumnController::class, 'index']
            );

            /**
             * =================================================
             * TASKS
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks',
                [TaskController::class, 'index']
            );

            Route::get(
                '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks/{task}',
                [TaskController::class, 'show']
            );

            /**
             * =================================================
             * NOTES
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/notes',
                [NoteController::class, 'index']
            );

            Route::post(
                '/workspaces/{workspace}/notes',
                [NoteController::class, 'store']
            );

            Route::get(
                '/workspaces/{workspace}/notes/{note}',
                [NoteController::class, 'show']
            );

            Route::put(
                '/workspaces/{workspace}/notes/{note}',
                [NoteController::class, 'update']
            );

            /**
             * =================================================
             * COMMENTS
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/notes/{note}/comments',
                [CommentController::class, 'index']
            );

            Route::post(
                '/workspaces/{workspace}/notes/{note}/comments',
                [CommentController::class, 'store']
            );

            Route::get(
                '/workspaces/{workspace}/notes/{note}/comments/{comment}',
                [CommentController::class, 'show']
            );

            /**
             * =================================================
             * FILES
             * =================================================
             */

            Route::get(
                '/workspaces/{workspace}/files',
                [FileController::class, 'index']
            );

            Route::post(
                '/workspaces/{workspace}/files',
                [FileController::class, 'store']
            );

            Route::get(
                '/workspaces/{workspace}/files/{file}',
                [FileController::class, 'show']
            );

            Route::get(
                '/workspaces/{workspace}/files/{file}/download',
                [FileController::class, 'download']
            );

            /**
             * =================================================
             * ADMIN ONLY
             * =================================================
             */

            Route::middleware('workspace.admin')->group(function () {

                /**
                 * =============================================
                 * MEMBERS
                 * =============================================
                 */

                Route::post(
                    '/workspaces/{workspace}/members',
                    [WorkspaceController::class, 'addMember']
                );

                /**
                 * =============================================
                 * BOARDS
                 * =============================================
                 */

                Route::post(
                    '/workspaces/{workspace}/boards',
                    [BoardController::class, 'store']
                );

                Route::put(
                    '/workspaces/{workspace}/boards/{board}',
                    [BoardController::class, 'update']
                );

                Route::delete(
                    '/workspaces/{workspace}/boards/{board}',
                    [BoardController::class, 'destroy']
                );

                /**
                 * =============================================
                 * COLUMNS
                 * =============================================
                 */

                Route::post(
                    '/workspaces/{workspace}/boards/{board}/columns',
                    [KanbanColumnController::class, 'store']
                );

                Route::put(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}',
                    [KanbanColumnController::class, 'update']
                );

                Route::delete(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}',
                    [KanbanColumnController::class, 'destroy']
                );

                /**
                 * =============================================
                 * TASKS
                 * =============================================
                 */

                Route::post(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks',
                    [TaskController::class, 'store']
                );

                Route::put(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks/{task}',
                    [TaskController::class, 'update']
                );

                Route::delete(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks/{task}',
                    [TaskController::class, 'destroy']
                );

                /**
                 * MOVE TASK
                 */

                Route::patch(
                    '/workspaces/{workspace}/boards/{board}/columns/{column}/tasks/{task}/move',
                    [TaskController::class, 'move']
                );

                /**
                 * =============================================
                 * NOTES
                 * =============================================
                 */

                Route::delete(
                    '/workspaces/{workspace}/notes/{note}',
                    [NoteController::class, 'destroy']
                );

                /**
                 * =============================================
                 * COMMENTS
                 * =============================================
                 */

                Route::delete(
                    '/workspaces/{workspace}/notes/{note}/comments/{comment}',
                    [CommentController::class, 'destroy']
                );

                /**
                 * =============================================
                 * FILES
                 * =============================================
                 */

                Route::delete(
                    '/workspaces/{workspace}/files/{file}',
                    [FileController::class, 'destroy']
                );
            });
        });
    });
});