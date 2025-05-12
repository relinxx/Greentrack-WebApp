<?php
class Gamification {
    private $conn;
    private $table_name = "user_gamification";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get user's total XP
    public function getXP($user_id) {
        $query = "SELECT xp_count 
                 FROM user_xp 
                 WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['xp_count'] : 0;
    }

    // Get user's badges
    public function getBadges($user_id) {
        $query = "SELECT b.*, ub.earned_at 
                 FROM badges b 
                 INNER JOIN user_badges ub ON b.id = ub.badge_id 
                 WHERE ub.user_id = :user_id 
                 ORDER BY ub.earned_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's tags
    public function getTags($user_id) {
        $query = "SELECT t.*, ut.assigned_at 
                 FROM tags t 
                 INNER JOIN user_tags ut ON t.id = ut.tag_id 
                 WHERE ut.user_id = :user_id 
                 ORDER BY ut.assigned_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's volunteer hours
    public function getVolunteerHours($user_id) {
        $query = "SELECT COALESCE(SUM(hours), 0) as total_hours 
                 FROM volunteer_hours 
                 WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_hours'];
    }

    // Add XP to user
    public function addXP($user_id, $xp) {
        $query = "INSERT INTO user_xp (user_id, xp_count) 
                 VALUES (:user_id, :xp) 
                 ON DUPLICATE KEY UPDATE xp_count = xp_count + :xp";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":xp", $xp);

        return $stmt->execute();
    }

    // Award badge to user
    public function awardBadge($user_id, $badge_id) {
        $query = "INSERT INTO user_badges (user_id, badge_id) 
                 VALUES (:user_id, :badge_id) 
                 ON DUPLICATE KEY UPDATE earned_at = earned_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":badge_id", $badge_id);

        return $stmt->execute();
    }

    // Assign tag to user
    public function assignTag($user_id, $tag_id) {
        $query = "INSERT INTO user_tags (user_id, tag_id) 
                 VALUES (:user_id, :tag_id) 
                 ON DUPLICATE KEY UPDATE assigned_at = assigned_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":tag_id", $tag_id);

        return $stmt->execute();
    }

    // Add volunteer hours
    public function addVolunteerHours($user_id, $hours, $activity) {
        $query = "INSERT INTO volunteer_hours (user_id, hours, activity_description) 
                 VALUES (:user_id, :hours, :activity)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":hours", $hours);
        $stmt->bindParam(":activity", $activity);

        return $stmt->execute();
    }
}
?> 