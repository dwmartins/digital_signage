<?php

namespace App\Domains\Audit\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Converte a data inicial escolhida no front para UTC.
     */
    private function localDateStartAsUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $date, 'America/Sao_Paulo')
            ->startOfDay()
            ->utc();
    }

    /**
     * Converte a data final escolhida no front para UTC.
     */
    private function localDateEndAsUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $date, 'America/Sao_Paulo')
            ->endOfDay()
            ->utc();
    }

    /**
     * Monta um nome simples para o registro afetado pela auditoria.
     */
    private function affectedItem(AuditLog $log): ?string
    {
        $values = $log->new_values ?: $log->old_values ?: [];

        if ($fullName = $this->fullNameFromValues($values)) {
            return $fullName;
        }

        foreach (['name', 'legal_name', 'email', 'document', 'title', 'slug'] as $field) {
            if (!empty($values[$field])) {
                return (string) $values[$field];
            }
        }

        if ($log->auditable) {
            return $this->affectedItemFromAuditable($log);
        }

        if ($log->auditable_type && $log->auditable_id) {
            return class_basename($log->auditable_type) . " #{$log->auditable_id}";
        }

        return null;
    }

    /**
     * Retorna um nome quando a auditoria tem campos de identificação.
     */
    private function fullNameFromValues(array $values): ?string
    {
        $fullName = trim(implode(' ', array_filter([
            $values['name'] ?? null,
            $values['last_name'] ?? null,
        ])));

        return $fullName !== '' ? $fullName : null;
    }

    /**
     * Usa a model relacionada quando os valores antigos/novos não têm nome.
     */
    private function affectedItemFromAuditable(AuditLog $log): ?string
    {
        $auditable = $log->auditable;

        foreach (['full_name', 'name', 'legal_name', 'email', 'document', 'title', 'slug'] as $field) {
            if (!empty($auditable->{$field})) {
                return (string) $auditable->{$field};
            }
        }

        return class_basename($log->auditable_type) . " #{$log->auditable_id}";
    }
}
