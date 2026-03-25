<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 24 — Schema corrections
 *
 * 1. Table rename: booking_waitlist → booking_waitlists
 * 2. INT → BIGINT for all PKs and FK columns across 13 tables
 * 3. Semantic column renames:
 *      bookings.user_id          → owner_id
 *      bookings.booked_by_user_id → creator_id
 *      bookings.reviewed_by      → reviewer_id
 *      room_blackouts.created_by  → creator_id
 *      user_invites.invited_by    → inviter_id
 *      equipment_transfers.transferred_by → handler_id
 *      notifications.user_id     → recipient_id
 *      booking_waitlists.user_id  → waiter_id
 *      booking_comments.user_id   → author_id
 *      booking_ratings.user_id    → rater_id
 */
class SchemaCorrections extends Migration
{
    /** Returns the column names of a given table as a keyed array. */
    private function cols(string $table): array
    {
        return array_flip($this->db->getFieldNames($table));
    }

    public function up(): void
    {
        // ── 1. Table rename (skip if already done) ────────────────────────────
        $tables = $this->db->listTables();
        if (in_array('booking_waitlist', $tables) && !in_array('booking_waitlists', $tables)) {
            $this->db->query('RENAME TABLE `booking_waitlist` TO `booking_waitlists`');
        }

        // ── 2. bookings ───────────────────────────────────────────────────────
        $bk = $this->cols('bookings');
        $parts = [
            'CHANGE COLUMN `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
            'CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL',
            'CHANGE COLUMN `room_id` `room_id` BIGINT UNSIGNED NOT NULL',
            'CHANGE COLUMN `recurrence_parent_id` `recurrence_parent_id` BIGINT UNSIGNED NULL DEFAULT NULL',
        ];
        if (isset($bk['user_id']))            $parts[] = 'CHANGE COLUMN `user_id` `owner_id` BIGINT UNSIGNED NOT NULL';
        else                                  $parts[] = 'CHANGE COLUMN `owner_id` `owner_id` BIGINT UNSIGNED NOT NULL';
        if (isset($bk['reviewed_by']))        $parts[] = 'CHANGE COLUMN `reviewed_by` `reviewer_id` BIGINT UNSIGNED NULL DEFAULT NULL';
        else                                  $parts[] = 'CHANGE COLUMN `reviewer_id` `reviewer_id` BIGINT UNSIGNED NULL DEFAULT NULL';
        if (isset($bk['booked_by_user_id'])) $parts[] = 'CHANGE COLUMN `booked_by_user_id` `creator_id` BIGINT UNSIGNED NULL DEFAULT NULL';
        else                                  $parts[] = 'CHANGE COLUMN `creator_id` `creator_id` BIGINT UNSIGNED NULL DEFAULT NULL';
        $this->db->query('ALTER TABLE `bookings` ' . implode(', ', $parts));

        // ── 3. buildings ──────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `buildings`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 4. rooms ──────────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `rooms`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `building_id`    `building_id`    BIGINT UNSIGNED NOT NULL
        ");

        // ── 5. equipment ──────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `equipment`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 6. booking_equipment ──────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `booking_equipment`
                CHANGE COLUMN `id`           `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `booking_id`   `booking_id`   BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `equipment_id` `equipment_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 7. holidays ───────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `holidays`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 8. booking_ratings ────────────────────────────────────────────────
        $br = $this->cols('booking_ratings');
        $raterCol = isset($br['user_id']) ? '`user_id` `rater_id`' : '`rater_id` `rater_id`';
        $this->db->query("
            ALTER TABLE `booking_ratings`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `booking_id`     `booking_id`     BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN {$raterCol}      BIGINT UNSIGNED NOT NULL
        ");

        // ── 9. room_blackouts ─────────────────────────────────────────────────
        $rb = $this->cols('room_blackouts');
        $creatorCol = isset($rb['created_by']) ? '`created_by` `creator_id`' : '`creator_id` `creator_id`';
        $this->db->query("
            ALTER TABLE `room_blackouts`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `room_id`        `room_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN {$creatorCol}    BIGINT UNSIGNED NULL DEFAULT NULL
        ");

        // ── 10. notifications ─────────────────────────────────────────────────
        $nt = $this->cols('notifications');
        $recipCol = isset($nt['user_id']) ? '`user_id` `recipient_id`' : '`recipient_id` `recipient_id`';
        $this->db->query("
            ALTER TABLE `notifications`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN {$recipCol}      BIGINT UNSIGNED NOT NULL
        ");

        // ── 11. booking_waitlists ─────────────────────────────────────────────
        $wl = $this->cols('booking_waitlists');
        $waiterCol = isset($wl['user_id']) ? '`user_id` `waiter_id`' : '`waiter_id` `waiter_id`';
        $this->db->query("
            ALTER TABLE `booking_waitlists`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN {$waiterCol}     BIGINT UNSIGNED NOT NULL
        ");

        // ── 12. booking_comments ──────────────────────────────────────────────
        $bc = $this->cols('booking_comments');
        $authorCol = isset($bc['user_id']) ? '`user_id` `author_id`' : '`author_id` `author_id`';
        $this->db->query("
            ALTER TABLE `booking_comments`
                CHANGE COLUMN `id`             `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `booking_id`     `booking_id`     BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN {$authorCol}     BIGINT UNSIGNED NOT NULL
        ");

        // ── 13. equipment_transfers ───────────────────────────────────────────
        $et = $this->cols('equipment_transfers');
        $handlerCol = isset($et['transferred_by']) ? '`transferred_by` `handler_id`' : '`handler_id` `handler_id`';
        $this->db->query("
            ALTER TABLE `equipment_transfers`
                CHANGE COLUMN `id`                  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id`       `institution_id`      BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `equipment_id`         `equipment_id`        BIGINT UNSIGNED NOT NULL,
                CHANGE COLUMN `origin_room_id`       `origin_room_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN `destination_room_id`  `destination_room_id` BIGINT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN {$handlerCol}           BIGINT UNSIGNED NOT NULL
        ");

        // ── 14. user_invites (has FK constraint on invited_by) ────────────────
        $ui = $this->cols('user_invites');
        if (isset($ui['invited_by'])) {
            $this->db->query("
                ALTER TABLE `user_invites`
                    DROP FOREIGN KEY `fk_user_invites_invited_by`,
                    CHANGE COLUMN `invited_by` `inviter_id` BIGINT UNSIGNED NOT NULL,
                    ADD CONSTRAINT `fk_user_invites_inviter_id`
                        FOREIGN KEY (`inviter_id`) REFERENCES `users`(`id`)
                        ON UPDATE CASCADE ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        // ── 14. user_invites ──────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `user_invites`
                DROP FOREIGN KEY `fk_user_invites_inviter_id`,
                CHANGE COLUMN `inviter_id` `invited_by` BIGINT UNSIGNED NOT NULL,
                ADD CONSTRAINT `fk_user_invites_invited_by`
                    FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE CASCADE
        ");

        // ── 13. equipment_transfers ───────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `equipment_transfers`
                CHANGE COLUMN `id`                  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id`       `institution_id`       INT UNSIGNED NOT NULL,
                CHANGE COLUMN `equipment_id`         `equipment_id`         INT UNSIGNED NOT NULL,
                CHANGE COLUMN `origin_room_id`       `origin_room_id`       INT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN `destination_room_id`  `destination_room_id`  INT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN `handler_id`           `transferred_by`       INT UNSIGNED NOT NULL
        ");

        // ── 12. booking_comments ──────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `booking_comments`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `booking_id`     `booking_id`     INT UNSIGNED NOT NULL,
                CHANGE COLUMN `author_id`      `user_id`        INT UNSIGNED NOT NULL
        ");

        // ── 11. booking_waitlists ─────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `booking_waitlists`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `booking_id`     `booking_id`     INT UNSIGNED NOT NULL,
                CHANGE COLUMN `waiter_id`      `user_id`        INT UNSIGNED NOT NULL
        ");

        // ── 10. notifications ─────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `notifications`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `recipient_id`   `user_id`        INT UNSIGNED NOT NULL
        ");

        // ── 9. room_blackouts ─────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `room_blackouts`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `room_id`        `room_id`        INT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN `creator_id`     `created_by`     INT UNSIGNED NULL DEFAULT NULL
        ");

        // ── 8. booking_ratings ────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `booking_ratings`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `booking_id`     `booking_id`     INT UNSIGNED NOT NULL,
                CHANGE COLUMN `rater_id`       `user_id`        INT UNSIGNED NOT NULL
        ");

        // ── 7. holidays ───────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `holidays`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL
        ");

        // ── 6. booking_equipment ──────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `booking_equipment`
                CHANGE COLUMN `id`           `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `booking_id`   `booking_id`   INT UNSIGNED NOT NULL,
                CHANGE COLUMN `equipment_id` `equipment_id` INT UNSIGNED NOT NULL
        ");

        // ── 5. equipment ──────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `equipment`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL
        ");

        // ── 4. rooms ──────────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `rooms`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL,
                CHANGE COLUMN `building_id`    `building_id`    INT UNSIGNED NOT NULL
        ");

        // ── 3. buildings ──────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `buildings`
                CHANGE COLUMN `id`             `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id` `institution_id` INT UNSIGNED NOT NULL
        ");

        // ── 2. bookings ───────────────────────────────────────────────────────
        $this->db->query("
            ALTER TABLE `bookings`
                CHANGE COLUMN `creator_id` `booked_by_user_id` INT UNSIGNED NULL DEFAULT NULL
        ");

        $this->db->query("
            ALTER TABLE `bookings`
                CHANGE COLUMN `id`                    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE COLUMN `institution_id`         `institution_id`       INT UNSIGNED NOT NULL,
                CHANGE COLUMN `owner_id`               `user_id`              INT UNSIGNED NOT NULL,
                CHANGE COLUMN `room_id`                `room_id`              INT UNSIGNED NOT NULL,
                CHANGE COLUMN `reviewer_id`            `reviewed_by`          INT UNSIGNED NULL DEFAULT NULL,
                CHANGE COLUMN `recurrence_parent_id`   `recurrence_parent_id` INT UNSIGNED NULL DEFAULT NULL
        ");

        // ── 1. Table rename ───────────────────────────────────────────────────
        $this->db->query('RENAME TABLE `booking_waitlists` TO `booking_waitlist`');
    }
}
