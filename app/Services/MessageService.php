<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Collection;

class MessageService
{
    /**
     * Get conversation list for a user
     *
     * Returns grouped conversations with latest message from each partner
     *
     * @param int $userId
     * @param string $userType (buyer|vendor)
     * @return array
     */
    public function getConversations(int $userId, string $userType): array
    {
        // Get last 100 messages for this user (performance limit)
        $allMessages = Message::forUser($userId, $userType)
            ->with(['sender:id,business_name', 'recipient:id,business_name'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        if ($allMessages->isEmpty()) {
            return [];
        }

        // Group by conversation partner
        $conversations = $allMessages->groupBy(function ($message) use ($userId, $userType) {
            // Determine who the conversation partner is
            if ($message->sender_id === $userId && $message->sender_type === $userType) {
                return $message->recipient_type . '_' . $message->recipient_id;
            }

            return $message->sender_type . '_' . $message->sender_id;
        })->map(function ($messages, $key) use ($userId, $userType) {
            $latestMessage = $messages->first();

            // Determine conversation partner
            $isUserSender = $latestMessage->sender_id === $userId && $latestMessage->sender_type === $userType;

            $partnerId = $isUserSender
                ? $latestMessage->recipient_id
                : $latestMessage->sender_id;

            $partnerType = $isUserSender
                ? $latestMessage->recipient_type
                : $latestMessage->sender_type;

            $partner = $isUserSender
                ? $latestMessage->recipient
                : $latestMessage->sender;

            // Count unread messages from this partner
            $unreadCount = $messages
                ->where('recipient_id', $userId)
                ->where('recipient_type', $userType)
                ->where('is_read', false)
                ->count();

            return [
                'partner_id' => $partnerId,
                'partner_type' => $partnerType,
                'partner_name' => $partner?->business_name ?? 'Unknown',
                'last_message' => $latestMessage->message ?? $latestMessage->content,
                'time' => $latestMessage->created_at->diffForHumans(),
                'unread' => $unreadCount > 0,
                'unread_count' => $unreadCount,
            ];
        })->values()->toArray();

        return $conversations;
    }

    /**
     * Get messages between two users
     *
     * @param int $userId
     * @param string $userType
     * @param int $partnerId
     * @param string $partnerType
     * @return Collection
     */
    public function getChatMessages(int $userId, string $userType, int $partnerId, string $partnerType): Collection
    {
        return Message::betweenUsers($userId, $userType, $partnerId, $partnerType)
            ->with(['sender:id,business_name'])
            ->orderBy('created_at', 'asc')
            ->limit(200) // Last 200 messages in conversation
            ->get();
    }

    /**
     * Send a message
     *
     * @param int $senderId
     * @param string $senderType
     * @param int $recipientId
     * @param string $recipientType
     * @param string $content
     * @param int|null $quoteId
     * @return Message
     */
    public function sendMessage(
        int $senderId,
        string $senderType,
        int $recipientId,
        string $recipientType,
        string $content,
        ?int $quoteId = null
    ): Message {
        $message = Message::create([
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'recipient_id' => $recipientId,
            'recipient_type' => $recipientType,
            'message' => $content,
            'quote_id' => $quoteId,
            'is_read' => false,
        ]);

        // Broadcast via WebSocket
        event(new \App\Events\MessageSent($message));

        return $message;
    }

    /**
     * Mark all messages from a partner as read
     *
     * @param int $userId
     * @param string $userType
     * @param int $partnerId
     * @param string $partnerType
     * @return int Number of messages marked as read
     */
    public function markConversationRead(int $userId, string $userType, int $partnerId, string $partnerType): int
    {
        $messages = Message::where('sender_id', $partnerId)
            ->where('sender_type', $partnerType)
            ->where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->where('is_read', false)
            ->get();

        $messages->each->markAsRead();

        return $messages->count();
    }

    /**
     * Get unread message count for user
     *
     * @param int $userId
     * @param string $userType
     * @return int
     */
    public function getUnreadCount(int $userId, string $userType): int
    {
        return Message::where('recipient_id', $userId)
            ->where('recipient_type', $userType)
            ->unread()
            ->count();
    }
}
