<?php

// SPDX-License-Identifier: GPL-3.0-or-later

class SystemInfo {
    /**
     * @return string
     */
    public static function loadavg() {
        $loadavg       = shell_exec("uptime | awk -F ': ' '{ print $2; }'");
        $process_count = shell_exec('ps ax | wc -l');
        if (!is_string($loadavg) || !is_string($process_count)) {
            return '';
        }

        return trim($loadavg).' '.trim($process_count);
    }

    /**
     * @return array<string,string|int>
     */
    public static function cpuinfo() {
        $info = @file('/proc/cpuinfo');
        $res  = [
            'model' => '<h4>Unknown</h4>',
            'count' => '-',
        ];
        if (!is_array($info)) {
            return $res;
        }

        $info = implode('', $info);
        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([^\:]+)[\r\n]+/s", $info, $model);
        @preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $info, $mhz);
        @preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $info, $cache);
        if (is_array($model[1]) !== false) {
            $cpu_count     = count($model[1]);
            $cpu_model     = $model[1][0];
            $cpu_frequency = $mhz[1][0];
            $cpu_cache     = $cache[1][0];

            $model_template     = "<h4>{$cpu_model}</h4>";
            $frequency_template = " <span style=\"color:#999;font-weight:600\">Frequency:</span> {$cpu_frequency}";
            $cahce_template     = " <span style=\"color:#999;font-weight:600\">Secondary cache:</span> {$cpu_cache}";

            $res['model']     = $model_template.$frequency_template.'<br/>'.$cahce_template;
            $res['count']     = $cpu_count;
            $res['frequency'] = $cpu_frequency;
            $res['cache']     = $cpu_cache;
        }

        return $res;
    }

    /**
     * @return array<string,float>
     */
    public static function meminfo() {
        $info = @file('/proc/meminfo');
        $res  = [];
        if (!is_array($info)) {
            return $res;
        }
        foreach ($info as $line) {
            $ar_buf          = explode(':', $line);
            $ar_buf          = array_map('trim', $ar_buf);
            $res[$ar_buf[0]] = (float) $ar_buf[1];
        }
        $res['MemUsed']    = $res['MemTotal'] - $res['MemFree'];
        $res['MemPercent'] = $res['MemUsed'] / $res['MemTotal'] * 100;

        $res['MemRealUsed']    = $res['MemUsed'] - $res['Cached'] - $res['Buffers']; // Real memory usage
        $res['MemRealFree']    = $res['MemTotal'] - $res['MemRealUsed']; // Real idle
        $res['MemRealPercent'] = $res['MemRealUsed'] / $res['MemTotal'] * 100; // Real memory usage
        $res['CachedPercent']  = $res['Cached'] / $res['MemTotal'] * 100; // Cached memory usage

        $res['SwapUsed']    = $res['SwapTotal'] - $res['SwapFree'];
        $res['SwapPercent'] = $res['SwapUsed'] / $res['SwapTotal'] * 100;

        return $res;
    }

    /**
     * @return array<string,array<int>>
     */
    public static function netinfo() {
        $info = @file('/proc/net/dev');
        $res  = [];
        if (!is_array($info)) {
            return $res;
        }

        $Receive  = [];
        $Transmit = [];

        for ($i = 2; $i < count($info); ++$i) {
            preg_match_all("/(?<name>[^\s]+):[\s]{0,}(?<rx_bytes>\d+)\s+(?:\d+\s+){7}(?<tx_bytes>\d+)\s+/", $info[$i], $group);
            $Receive[$i - 2]  = (int) $group['rx_bytes'][0]; // Receive data in bytes
            $Transmit[$i - 2] = (int) $group['tx_bytes'][0]; // Transmit data in bytes
        }
        $res['Receive']  = $Receive;
        $res['Transmit'] = $Transmit;

        return $res;
    }

    /**
     * @return array<int,string>
     */
    public static function enuminterface() {
        $info = @file('/proc/net/dev');
        $res  = [];
        if (!is_array($info)) {
            return $res;
        }
        for ($i = 2; $i < count($info); ++$i) {
            preg_match_all("/(?<name>[^\s]+):[\s]{0,}(?<rx_bytes>\d+)\s+(?:\d+\s+){7}(?<tx_bytes>\d+)\s+/", $info[$i], $group);
            $res[$i - 2] = $group['name'][0];
        }

        return $res;
    }
}
