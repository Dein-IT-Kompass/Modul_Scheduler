<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointment_resources_model extends EA_Model
{
    private const LOCATION_MODES = [
        'appointment',
        'customer',
        'project',
        'default',
        'custom',
    ];

    public function get_by_appointment(int $appointment_id): array
    {
        return $this->db
            ->select(
                'appointment_resources.*,
                 planning_resources.name,
                 planning_resources.resource_type,
                 planning_resources.inventory_number,
                 planning_resources.location AS default_location,
                 planning_resources.color'
            )
            ->from('appointment_resources')
            ->join(
                'planning_resources',
                'planning_resources.id = appointment_resources.id_planning_resources',
                'inner'
            )
            ->where('appointment_resources.id_appointments', $appointment_id)
            ->order_by('planning_resources.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function validate_assignments(
        array $assignments,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null
    ): void {
        $seen = [];

        foreach ($assignments as $assignment) {
            $resource_id = (int) ($assignment['id_planning_resources'] ?? 0);
            $location_mode = $assignment['location_mode'] ?? 'appointment';

            if ($resource_id < 1 || isset($seen[$resource_id])) {
                continue;
            }

            $seen[$resource_id] = true;

            if (!in_array($location_mode, self::LOCATION_MODES, true)) {
                throw new InvalidArgumentException(
                    'Ungültiger Standortmodus für eine Ressource.'
                );
            }

            if (
                $location_mode === 'custom' &&
                trim((string) ($assignment['location_override'] ?? '')) === ''
            ) {
                throw new InvalidArgumentException(
                    'Für einen abweichenden Ressourcenstandort muss ein Standort angegeben werden.'
                );
            }

            $resource = $this->db
                ->get_where('planning_resources', [
                    'id' => $resource_id,
                    'is_active' => 1,
                ])
                ->row_array();

            if (!$resource) {
                throw new RuntimeException(
                    'Eine ausgewählte Ressource existiert nicht oder ist inaktiv.'
                );
            }

            $this->db
                ->select(
                    'appointments.id,
                     appointments.start_datetime,
                     appointments.end_datetime'
                )
                ->from('appointment_resources')
                ->join(
                    'appointments',
                    'appointments.id = appointment_resources.id_appointments',
                    'inner'
                )
                ->where(
                    'appointment_resources.id_planning_resources',
                    $resource_id
                )
                ->where('appointments.start_datetime <', $end_datetime)
                ->where('appointments.end_datetime >', $start_datetime);

            if ($exclude_appointment_id !== null) {
                $this->db->where(
                    'appointments.id !=',
                    $exclude_appointment_id
                );
            }

            $conflict = $this->db->get()->row_array();

            if ($conflict) {
                throw new RuntimeException(
                    'Ressourcenkonflikt: ' .
                    $resource['name'] .
                    ' ist bereits von ' .
                    date('d.m.Y H:i', strtotime($conflict['start_datetime'])) .
                    ' bis ' .
                    date('d.m.Y H:i', strtotime($conflict['end_datetime'])) .
                    ' eingeplant.'
                );
            }
        }
    }

    public function save_assignments(
        int $appointment_id,
        array $assignments
    ): void {
        $this->db->delete(
            'appointment_resources',
            ['id_appointments' => $appointment_id]
        );

        $now = date('Y-m-d H:i:s');
        $seen = [];

        foreach ($assignments as $assignment) {
            $resource_id = (int) ($assignment['id_planning_resources'] ?? 0);

            if ($resource_id < 1 || isset($seen[$resource_id])) {
                continue;
            }

            $seen[$resource_id] = true;

            $location_mode = $assignment['location_mode'] ?? 'appointment';

            $this->db->insert('appointment_resources', [
                'id_appointments' => $appointment_id,
                'id_planning_resources' => $resource_id,
                'location_mode' => $location_mode,
                'location_override' =>
                    $location_mode === 'custom'
                        ? trim(
                            (string) (
                                $assignment['location_override'] ?? ''
                            )
                        )
                        : null,
                'create_datetime' => $now,
                'update_datetime' => $now,
            ]);
        }
    }
}
