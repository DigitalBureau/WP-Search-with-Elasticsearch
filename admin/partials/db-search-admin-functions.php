<?php
$options = get_option('_' . $this->db_search);
$esindex = !empty($options['esindex']) ? $options['esindex'] : preg_replace('/[^A-Za-z0-9\-\']/', '', get_bloginfo('name'));
settings_fields("_" . $this->db_search);
do_settings_sections("_" . $this->db_search);

function test_es_connection($options) {
    $url = $options['esurl'] . ':' . $options['esurlport'];
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $val     = curl_exec($ch);
    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $val = json_decode($val, true);

    //ES must be at least 5.0
    if (floatval($val['version']['number']) < 5.5) {
        return false;
    }

    if (200 == $retcode) {
        return true;
    }
    return false;
}

try {
    $es     = new db_Search();
    $client = $es->build_es_client();
} catch (Exception $e) {
    $client      = false;
    $index_error = 'There was a problem with your Elasticsearch setup. Please delete and reindex your instance, or check your connection. Error message:' . $e->getMessage();
}

//checkboxes
if (!empty($options)) {
    foreach ($options as $o => $v) {
        if ($options[$o] === 'on') {
            $$o = 'checked="checked"';
        }
    }
}

//if no url/port specified, disable index options
if (!$options['esurl'] && !$options['esurlport']) {
    $createIndex    = 'disabled';
    $health_display = 'none';
} else {
    //test health
    if (test_es_connection($options)) {
        try {
            $params         = ['index' => $esindex, 'type' => 'article'];
            $health         = $client->cluster()->health();
            $health_display = 'block';
        } catch (Exception $e) {
            $index_error = 'There was a problem with your Elasticsearch setup. Please delete and reindex your instance, or check your connection. <br>Error message:' . $e->getMessage();
        }
    } else {
        $index_error = 'Your connection to Elasticsearch appears to be invalid. Please verify your instance url, port, that you have allowed for inbound requests to your instance, and are using Elasticsearch version 5.5 and above.';
    }

}

//if index has been created, disable index params
if ($options['activatedIndex'] && $options['populatedIndex']) {
    $created       = 'readonly';
    $cb_created    = 'onclick="return false;"';
    $created_css   = 'filter:grayscale(100)';
    $createIndex   = 'disabled';
    $populateIndex = 'disabled';
    $index_display = 'block';
    try {
        $count            = $client->count($params);
        $count            = $count['count'];
        $es_index_display = $client->indices()->getSettings(array('index' => $options['esindex']));
        $es_index_display = $es_index_display[$options['esindex']]['settings']['index'];
    } catch (Exception $e) {
        $index_error = 'There was a problem with your Elasticsearch setup. Please delete and reindex your instance, or check your connection. Error message:' . $e->getMessage();
    }
} else if ($options['activatedIndex']) {
    $created     = 'readonly';
    $cb_created  = 'onclick="return false;"';
    $created_css = 'filter:grayscale(100)';
    $createIndex = 'disabled';

} else {
    $populateIndex = 'disabled';
    $index_display = 'none';
}

$so  = 'sort_' . $options['sort'];
$$so = 'selected';

// print_r($options);
