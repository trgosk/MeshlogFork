<?php
$start = microtime(true);
require_once "../../../lib/meshlog.class.php";
require_once "../../../config.php";
include "../utils.php";

function getCreatedAtTimestamp($value) {
    if (!$value) return 0;

    $timestamp = strtotime($value);
    return $timestamp === false ? 0 : $timestamp;
}

function trimReportContainers(&$results, $containers, $maxReports) {
    usort($containers, function($left, $right) {
        if ($left['created_at'] === $right['created_at']) {
            return $left['order'] <=> $right['order'];
        }

        return $right['created_at'] <=> $left['created_at'];
    });

    $remaining = max(0, (int) $maxReports);

    foreach ($containers as $container) {
        if ($container['type'] === 'contacts') {
            $reports =& $results['contacts']['objects'][$container['index']]['advertisement']['reports'];
        } else {
            $reports =& $results[$container['type']]['objects'][$container['index']]['reports'];
        }

        if (!is_array($reports)) {
            $reports = array();
            unset($reports);
            continue;
        }

        if ($remaining <= 0) {
            $reports = array();
            unset($reports);
            continue;
        }

        $reportCount = count($reports);
        if ($reportCount > $remaining) {
            $reports = array_slice($reports, 0, $remaining);
            $remaining = 0;
            unset($reports);
            continue;
        }

        $remaining -= $reportCount;
        unset($reports);
    }
}

function limitGetAllReports(&$results, $maxReports) {
    $contactContainers = array();
    $order = 0;

    if (isset($results['contacts']['objects']) && is_array($results['contacts']['objects'])) {
        foreach ($results['contacts']['objects'] as $index => $contact) {
            $advertisement = $contact['advertisement'] ?? null;
            if (!is_array($advertisement)) continue;

            $reports = $advertisement['reports'] ?? null;
            if (!is_array($reports) || !count($reports)) continue;

            $contactContainers[] = array(
                'type' => 'contacts',
                'index' => $index,
                'created_at' => getCreatedAtTimestamp($advertisement['created_at'] ?? $contact['created_at'] ?? null),
                'order' => $order++,
            );
        }
    }

    trimReportContainers($results, $contactContainers, $maxReports);

    foreach (array('advertisements', 'direct_messages', 'channel_messages') as $type) {
        $containers = array();
        $order = 0;

        if (!isset($results[$type]['objects']) || !is_array($results[$type]['objects'])) continue;

        foreach ($results[$type]['objects'] as $index => $object) {
            $reports = $object['reports'] ?? null;
            if (!is_array($reports) || !count($reports)) continue;

            $containers[] = array(
                'type' => $type,
                'index' => $index,
                'created_at' => getCreatedAtTimestamp($object['created_at'] ?? null),
                'order' => $order++,
            );
        }

        trimReportContainers($results, $containers, $maxReports);
    }
}

$meshlog = new MeshLog($config['db']);
$err = $meshlog->getError();
$reportLimit = getReportLimitParam(1);

if ($err) {
    $results = array('error' => $err);
} else {
    $params = array(
        'offset' => getParam('offset', 0),
        'count' => getParam('count', DEFAULT_COUNT),
        'after_ms' => getParam('after_ms', 0),
        'before_ms' => getParam('before_ms', 0),
    );

    $paramsContacts = array(
        'offset' => getParam('offset', 0),
        'count' => getParam('count', DEFAULT_CONTACTS_COUNT),
        'after_ms' => getParam('after_ms', 0),
        'before_ms' => getParam('before_ms', 0),
    );

    $reporters = $meshlog->getReporters($params);
    $contacts = $meshlog->getContactsQuick($paramsContacts);
    $advertisements = $meshlog->getAdvertisementsQuick($params);
    $channels = $meshlog->getChannels($params);
    $direct_messages = $meshlog->getDirectMessagesQuick($params);
    $channel_messages = $meshlog->getChannelMessagesQuick($params);

    $results = array(
        'reporters' => $reporters,
        'contacts' => $contacts,
        'advertisements' => $advertisements,
        'channels' => $channels,
        'direct_messages' => $direct_messages,
        'channel_messages' => $channel_messages
    );

    limitContactAdvertisementReportsPerReporter($results['contacts']['objects'], $reportLimit);
    limitObjectReportsPerReporter($results['advertisements']['objects'], $reportLimit);
    limitObjectReportsPerReporter($results['direct_messages']['objects'], $reportLimit);
    limitObjectReportsPerReporter($results['channel_messages']['objects'], $reportLimit);
    limitGetAllReports($results, MAX_GETALL_REPORTS_COUNT);
}

$results['time'] = microtime(true) - $start;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results);

?>
