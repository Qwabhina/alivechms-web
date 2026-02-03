<?php

declare(strict_types=1);

namespace AliveChMS\Core\Stats;

use AliveChMS\Core\System\ORM;

class MemberStats
{
    private ORM $orm;

    public function __construct()
    {
        $this->orm = new ORM();
    }

    /**
     * Get member statistics
     */
    public function getStats(): array
    {
        // Status counts
        $statusCounts = $this->orm->runQuery(
            "SELECT mst.StatusName as MbrMembershipStatus, COUNT(*) AS count
             FROM churchmember c
             JOIN membership_status mst ON c.MbrMembershipStatusID = mst.StatusID
             WHERE c.Deleted = 0
             GROUP BY mst.StatusName"
        );

        // New this month
        $monthStart = date('Y-m-01');
        $newThisMonth = $this->orm->runQuery(
            "SELECT COUNT(*) AS count FROM churchmember WHERE Deleted = 0 AND MbrRegistrationDate >= :month_start",
            [':month_start' => $monthStart]
        )[0]['count'] ?? 0;

        // Gender distribution
        $genderCounts = $this->orm->runQuery(
            "SELECT MbrGender, COUNT(*) AS count
             FROM churchmember c
             JOIN membership_status mst ON c.MbrMembershipStatusID = mst.StatusID
             WHERE c.Deleted = 0 AND mst.StatusName = 'Active'
             GROUP BY MbrGender"
        );

        // Age groups
        $ageGroups = $this->orm->runQuery(
            "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    WHEN TIMESTAMPDIFF(YEAR, MbrDateOfBirth, CURDATE()) > 60 THEN 'Over 60'
                    ELSE 'Unknown'
                END AS age_group,
                COUNT(*) AS count
             FROM churchmember c
             JOIN membership_status mst ON c.MbrMembershipStatusID = mst.StatusID
             WHERE c.Deleted = 0 AND mst.StatusName = 'Active'
             GROUP BY age_group"
        );

        return [
            'status_counts' => $statusCounts,
            'new_this_month' => $newThisMonth,
            'gender_counts' => $genderCounts,
            'age_groups' => $ageGroups
        ];
    }
}
