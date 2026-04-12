<?php

function getParam($key, $fallback=null) {
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key]))  return $_GET[$key];
    return $fallback;
}

function getReportLimitParam($fallback = 1) {
    $limit = intval(getParam('report_limit', $fallback));
    return $limit > 0 ? $limit : intval($fallback);
}

function limitReportsPerReporterArray($reports, $perReporterLimit) {
    if (!is_array($reports)) return array();

    $perReporterLimit = max(1, intval($perReporterLimit));
    $reports = array_values(array_filter($reports, function ($report) {
        return is_array($report)
            && array_key_exists('reporter_id', $report)
            && $report['reporter_id'] !== null;
    }));

    usort($reports, function ($left, $right) {
        $leftTime = strtotime($left['created_at'] ?? '') ?: 0;
        $rightTime = strtotime($right['created_at'] ?? '') ?: 0;
        if ($leftTime === $rightTime) {
            return intval($right['id'] ?? 0) <=> intval($left['id'] ?? 0);
        }

        return $rightTime <=> $leftTime;
    });

    $kept = array();
    $counts = array();

    foreach ($reports as $report) {
        $reporterId = strval($report['reporter_id']);
        $counts[$reporterId] = $counts[$reporterId] ?? 0;

        if ($counts[$reporterId] >= $perReporterLimit) {
            continue;
        }

        $counts[$reporterId] += 1;
        $kept[] = $report;
    }

    return $kept;
}

function limitObjectReportsPerReporter(&$objects, $perReporterLimit, $reportsKey = 'reports') {
    if (!is_array($objects)) return;

    foreach ($objects as &$object) {
        if (!is_array($object)) continue;
        $object[$reportsKey] = limitReportsPerReporterArray($object[$reportsKey] ?? array(), $perReporterLimit);
    }
    unset($object);
}

function limitContactAdvertisementReportsPerReporter(&$objects, $perReporterLimit) {
    if (!is_array($objects)) return;

    foreach ($objects as &$object) {
        if (!is_array($object)) continue;
        if (!isset($object['advertisement']) || !is_array($object['advertisement'])) continue;

        $object['advertisement']['reports'] = limitReportsPerReporterArray(
            $object['advertisement']['reports'] ?? array(),
            $perReporterLimit
        );
    }
    unset($object);
}

?>
