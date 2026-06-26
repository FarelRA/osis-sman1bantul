<?php

/**
 * Assignment Service
 * Handles random class and gate assignment for registrations with capacity limits
 */
class AssignmentService
{
    private RegistrationRepository $repo;

    public function __construct(RegistrationRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Assign class and gate to a registration
     * 
     * @param string $formId Form ID
     * @param string $regId Registration ID  
     * @param array $settings Assignment settings from form config
     * @param array $regData Registration data (to check special needs)
     * @return array ['class' => string, 'gate' => string] or empty if assignment disabled
     */
    public function assignClassAndGate(string $formId, string $regId, array $settings, array $regData): array
    {
        if (empty($settings['enabled'])) {
            return [];
        }

        $classes = $settings['classes'] ?? [];
        $gates = $settings['gates'] ?? [];

        if (empty($classes) || empty($gates)) {
            return [];
        }

        // Check for special needs
        $specialNeedsField = $settings['special_needs_field'] ?? 'special_needs';
        $specialNeedsValue = $settings['special_needs_value'] ?? 'Ya';
        $isSpecialNeeds = ($regData[$specialNeedsField] ?? '') === $specialNeedsValue;

        if ($isSpecialNeeds) {
            // Assign fixed class and gate for special needs
            $assignedClass = $settings['special_needs_class'] ?? 'Cue';
            $assignedGate = $settings['special_needs_gate'] ?? 'Registration Gate 1';
        } else {
            // Get current counts
            $classCounts = $this->repo->countByAssignment($formId, 'assigned_class');
            $gateCounts = $this->repo->countByAssignment($formId, 'assigned_gate');

            // Find available classes (not at max capacity)
            $availableClasses = [];
            foreach ($classes as $class) {
                $name = $class['name'];
                $max = $class['max'] ?? 36;
                $current = $classCounts[$name] ?? 0;
                if ($current < $max) {
                    $availableClasses[] = $name;
                }
            }

            // Find available gates (not at max capacity)
            $availableGates = [];
            foreach ($gates as $gate) {
                $name = $gate['name'];
                $max = $gate['max'] ?? 63;
                $current = $gateCounts[$name] ?? 0;
                if ($current < $max) {
                    $availableGates[] = $name;
                }
            }

            // Random selection from available options
            if (empty($availableClasses) || empty($availableGates)) {
                // All full - fallback to first option
                $assignedClass = $classes[0]['name'] ?? 'Unknown';
                $assignedGate = $gates[0]['name'] ?? 'Unknown';
            } else {
                $assignedClass = $availableClasses[array_rand($availableClasses)];
                $assignedGate = $availableGates[array_rand($availableGates)];
            }
        }

        // Save assignment to registration
        $this->repo->updateData($formId, $regId, [
            'assigned_class' => $assignedClass,
            'assigned_gate' => $assignedGate
        ]);

        return [
            'class' => $assignedClass,
            'gate' => $assignedGate
        ];
    }
}
