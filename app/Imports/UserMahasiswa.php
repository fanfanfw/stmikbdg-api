<?php

namespace App\Imports;

use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\DB;

// ? Models
use App\Models\Users\User;
use App\Models\Users\MahasiswaView;

class UserMahasiswa implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;
    private $failedRows = [];

    public function model(array $row)
    {
        /**
         * validasi input secara manual
         */
        if (empty($row['nim']) && empty($row['email']) && empty($row['password'])) {
            return null;
        }

        /**
         * validasi manual email
         */
        if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $this->failedRows[] = $row['nim'];
            return null;
        }

        if (User::where('email', $row['email'])->exists()) {
            $this->failedRows[] = $row['nim'];
            return null;
        }

        /**
         * validasi manual nim
         */
        if (User::where('kd_user', self::setKdUser($row['nim']))->exists()) {
            $this->failedRows[] = $row['nim'];
            return null;
        }

        if (!MahasiswaView::where('nim', ((string) $row['nim']))->exists()) {
            $this->failedRows[] = $row['nim'];
            return null;
        }

        $user = [
            'kd_user' => self::setKdUser($row['nim']),
            'email' => $row['email'],
            'password' => self::setPasswordUser($row['password']),
            'image' => 'college_student.png',
            'is_mhs' => true
        ];

        /**
         * insert ke db
         */
        DB::beginTransaction();
        try {
            $create = User::create($user);
            DB::commit();
            return $create;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->failedRows[] = $row['nim'];
        }
    }

    public function getFailedRows()
    {
        return array_unique($this->failedRows);
    }

    public function rules(): array
    {
        return [
            '*.nim' => 'required',
            '*.password' => 'required|string|min:8|max:64|regex:/^\S*$/u',
        ];
    }

    private function setKdUser($nim) {
        return 'MHS-' . $nim;
    }

    private function setPasswordUser($password) {
        return Hash::make($password);
    }
}
