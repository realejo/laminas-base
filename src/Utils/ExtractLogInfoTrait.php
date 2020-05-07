<?php

namespace Realejo\Utils;

trait ExtractLogInfoTrait
{
    protected function extractLogInfo(
        array &$set,
        string $logMessage = '',
        string $logType = '',
        $forceLogTypePrefix = false
    ): array {
        $idBlame = null;
        if (array_key_exists('blame', $set)) {
            $idBlame = $set['blame'];
            unset($set['blame']);
        }

        // Verifica se há uma mensagem para o log
        foreach (['logMessage', 'log'] as $possibleKey) {
            if (array_key_exists($possibleKey, $set)) {
                $logMessage = empty($set[$possibleKey]) ? $logMessage : $set[$possibleKey];
                unset($set[$possibleKey]);
            }
        }

        // Verifica se há uma mensagem para o log
        foreach (['logType'] as $possibleKey) {
            if (array_key_exists($possibleKey, $set)) {
                $logTypeCandidate = empty($set[$possibleKey]) ? $logType : $set[$possibleKey];
                if ($forceLogTypePrefix === true && strpos($logTypeCandidate, $logType) !== 0) {
                    $logTypeCandidate = $logType . $logTypeCandidate;
                }
                $logType = $logTypeCandidate;
                unset($set[$possibleKey]);
            }
        }

        // Remove o usuário eu está criando a promoção
        $logData = null;
        if (array_key_exists('logData', $set)) {
            $logData = $set['logData'];
            unset($set['logData']);
        }

        return [$idBlame, $logMessage, $logType, $logData];
    }
}
