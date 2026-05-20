<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * Get the users assigned to this unit.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the patient data entries for this unit.
     */
    public function patientData(): HasMany
    {
        return $this->hasMany(PatientData::class);
    }

    /**
     * Get the field definition for this unit.
     * Returns an array of field definitions specific to this unit.
     */
    public function getFieldDefinition(): array
    {
        $fieldDefinitions = [
            'IGD' => [
                [
                    'name' => 'Jumlah pasien rawat inap',
                    'key' => 'jumlah_pasien_rawat_inap',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien rawat jalan',
                    'key' => 'jumlah_pasien_rawat_jalan',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien pulang paksa',
                    'key' => 'jumlah_pasien_pulang_paksa',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Keterangan penyakit rawat inap',
                    'key' => 'keterangan_penyakit_rawat_inap',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'Keterangan penyakit rawat jalan',
                    'key' => 'keterangan_penyakit_rawat_jalan',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'Total',
                    'key' => 'total',
                    'type' => 'numeric',
                    'required' => false,
                    'auto_calculated' => true,
                    'calculation' => 'jumlah_pasien_rawat_inap + jumlah_pasien_rawat_jalan + jumlah_pasien_pulang_paksa',
                ],
            ],
            'Rawat Inap' => [
                [
                    'name' => 'Jumlah pasien anak',
                    'key' => 'jumlah_pasien_anak',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Dalam',
                    'key' => 'jumlah_pasien_dalam',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Saraf',
                    'key' => 'jumlah_pasien_saraf',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Obsgyn',
                    'key' => 'jumlah_pasien_obsgyn',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Bedah',
                    'key' => 'jumlah_pasien_bedah',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah inden',
                    'key' => 'jumlah_inden',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah RPL',
                    'key' => 'jumlah_rpl',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien pulang',
                    'key' => 'jumlah_pasien_pulang',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Total',
                    'key' => 'total',
                    'type' => 'numeric',
                    'required' => false,
                    'auto_calculated' => true,
                    'calculation' => 'jumlah_pasien_anak + jumlah_pasien_dalam + jumlah_pasien_saraf + jumlah_pasien_obsgyn + jumlah_pasien_bedah + jumlah_inden + jumlah_rpl + jumlah_pasien_pulang',
                ],
            ],
            'Rawat Jalan' => [
                [
                    'name' => 'Jumlah poli Obgyn',
                    'key' => 'jumlah_poli_obgyn',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah poli Dalam',
                    'key' => 'jumlah_poli_dalam',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah poli Anak',
                    'key' => 'jumlah_poli_anak',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah poli Bedah',
                    'key' => 'jumlah_poli_bedah',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah poli Saraf',
                    'key' => 'jumlah_poli_saraf',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah poli Fisioterapi',
                    'key' => 'jumlah_poli_fisioterapi',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Total',
                    'key' => 'total',
                    'type' => 'numeric',
                    'required' => false,
                    'auto_calculated' => true,
                    'calculation' => 'jumlah_poli_obgyn + jumlah_poli_dalam + jumlah_poli_anak + jumlah_poli_bedah + jumlah_poli_saraf + jumlah_poli_fisioterapi',
                ],
            ],
            'VK' => [
                [
                    'name' => 'Jumlah pasien VK',
                    'key' => 'jumlah_pasien_vk',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Keterangan',
                    'key' => 'keterangan',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
            'ICU' => [
                [
                    'name' => 'Jumlah pasien anak',
                    'key' => 'jumlah_pasien_anak',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Dalam',
                    'key' => 'jumlah_pasien_dalam',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Saraf',
                    'key' => 'jumlah_pasien_saraf',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Obsgyn',
                    'key' => 'jumlah_pasien_obsgyn',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Bedah',
                    'key' => 'jumlah_pasien_bedah',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Inden',
                    'key' => 'jumlah_pasien_inden',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien pulang',
                    'key' => 'jumlah_pasien_pulang',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
            ],
            'HCU' => [
                [
                    'name' => 'Jumlah pasien anak',
                    'key' => 'jumlah_pasien_anak',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Dalam',
                    'key' => 'jumlah_pasien_dalam',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Saraf',
                    'key' => 'jumlah_pasien_saraf',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Obsgyn',
                    'key' => 'jumlah_pasien_obsgyn',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Bedah',
                    'key' => 'jumlah_pasien_bedah',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien Inden',
                    'key' => 'jumlah_pasien_inden',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
                [
                    'name' => 'Jumlah pasien pulang',
                    'key' => 'jumlah_pasien_pulang',
                    'type' => 'numeric',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                ],
            ],
        ];

        return $fieldDefinitions[$this->name] ?? [];
    }
}
