<?php

namespace App\Models;

use CodeIgniter\Model;

class HolidayModel extends Model
{
    protected $table      = 'holidays';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'name',
        'date',
        'is_recurring',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'           => 'required|max_length[200]',
        'date'           => 'required|valid_date[Y-m-d]',
        'institution_id' => 'required|integer',
    ];

    /**
     * Checks if a given date is a holiday for the institution.
     * Accounts for recurring holidays (year-independent match on MM-DD).
     */
    public function isHoliday(int $institutionId, string $date): bool
    {
        $monthDay = substr($date, 5); // MM-DD

        // Exact date match
        $exact = $this->where('institution_id', $institutionId)
                       ->where('date', $date)
                       ->countAllResults();
        if ($exact > 0) {
            return true;
        }

        // Recurring: match on month-day portion
        $recurring = $this->where('institution_id', $institutionId)
                          ->where('is_recurring', 1)
                          ->where("DATE_FORMAT(`date`, '%m-%d')", $monthDay)
                          ->countAllResults();

        return $recurring > 0;
    }

    /**
     * Get holiday name(s) for a date (for error messages).
     */
    public function getForDate(int $institutionId, string $date): array
    {
        $monthDay = substr($date, 5);

        $exact = $this->where('institution_id', $institutionId)
                      ->where('date', $date)
                      ->findAll();

        $recurring = $this->where('institution_id', $institutionId)
                          ->where('is_recurring', 1)
                          ->where("DATE_FORMAT(`date`, '%m-%d')", $monthDay)
                          ->findAll();

        return array_unique(
            array_merge(
                array_column($exact, 'name'),
                array_column($recurring, 'name')
            )
        );
    }

    public function forInstitution(int $institutionId): array
    {
        return $this->where('institution_id', $institutionId)
                    ->orderBy('date', 'ASC')
                    ->findAll();
    }
}
