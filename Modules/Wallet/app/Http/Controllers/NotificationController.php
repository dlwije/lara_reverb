<?php
namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Transformers\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with filters
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $filters = $request->validate([
            'type' => 'sometimes|string|in:transaction,expiry_reminder,promotional,security,system',
            'read' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:5|max:100',
            'page' => 'sometimes|integer|min:1'
        ]);

        $perPage = $filters['per_page'] ?? 15;

        $query = $user->notifications()->with('notifiable');

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by read status
        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Order by latest first
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
                'total_count' => $user->notifications()->count(),
            ]
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        $user = auth()->user();

        $stats = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read,
                COUNT(DISTINCT type) as types_count
            ')
            ->first();

        $types = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $stats->total,
                'unread' => $stats->unread,
                'read' => $stats->read,
                'types_count' => $stats->types_count,
                'types_breakdown' => $types,
                'unread_percentage' => $stats->total > 0 ? round(($stats->unread / $stats->total) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();

        $affected = $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$affected} notifications marked as read",
            'data' => [
                'marked_read' => $affected
            ]
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        $user = auth()->user();

        $deleted = $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} notifications cleared",
            'data' => [
                'deleted_count' => $deleted
            ]
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = auth()->user();

        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Get recent notifications (last 7 days)
     */
    public function recent()
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications)
        ]);
    }
}
