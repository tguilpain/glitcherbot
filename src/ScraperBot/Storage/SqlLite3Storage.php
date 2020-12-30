<?php

namespace ScraperBot\Storage;

class SqlLite3Storage implements StorageInterface {

    private $pdo;

    /**
     * ResultsService constructor.
     * @param string $db
     */
    public function __construct($db = 'railerdb.sqlite3') {
        $this->pdo = new \SQLite3($db);

        $this->pdo->query("CREATE TABLE IF NOT EXISTS sites (
                            timestamp NOT NULL,
                            site_id INTEGER,
                            url TEXT NOT NULL,
                            size INTEGER,
                            statusCode INTEGER,
                            footprint TEXT
       );");

        $this->pdo->query("CREATE TABLE IF NOT EXISTS sitemapURLs (
                            timestamp NOT NULL,
                            site_id INTEGER,
                            url TEXT NOT NULL
       );");

        $this->pdo->query("CREATE TABLE IF NOT EXISTS pendingURLs (
                            timestamp NOT NULL,
                            site_id INTEGER,
                            url TEXT NOT NULL
       );");

    }

    /**
     * Add results.
     *
     * @param $destination
     * @param $sites
     */
    public function addResult($site_id, $site_url, $size, $status_code, $footprint, $timestamp) {
        date('dmY-His');
        $query = sprintf("INSERT INTO sites (site_id, url, size, statusCode, footprint, timestamp) VALUES(%d,\"%s\",%d,%d,\"%s\",\"%s\")",
            $site_id, $site_url, $size, $status_code, $footprint, $timestamp);
        $this->pdo->query($query);
    }

    /**
     * @return SQLite3Result
     */
    public function getResults() {
        $queryString = sprintf("SELECT * FROM sites ");
        $query = $this->pdo->query($queryString);
        while ($row = $query->fetchArray()) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Get different crawls
     *
     * @return mixed
     */
    public function getTimeStamps() {
        $queryString = sprintf("SELECT DISTINCT  * FROM sites group by timestamp");
        $query = $this->pdo->query($queryString);
        while ($row = $query->fetchArray()) {
            $results[] = $row['timestamp'];
        }

        return $results;
    }

    /**
     * Get different crawls
     *
     * @return mixed
     */
    public function getStatusCodes() {
        $queryString = sprintf("SELECT statusCode FROM sites group by StatusCode");
        $query = $this->pdo->query($queryString);
        while ($row = $query->fetchArray()) {
            $results[] = $row['statusCode'];
        }

        return $results;
    }

    /**
     * Get results for a given date.
     *
     * @param $timestamp
     * @return mixed
     */
    public function getResultsbyTimestamp($timestamp) {
        $queryString = sprintf("SELECT * FROM sites WHERE timestamp = '%s' order by url", $timestamp);
        $query = $this->pdo->query($queryString);
        while ($row = $query->fetchArray()) {
            $results[$timestamp][$row['url']] = $row;
        }

        return $results;
    }

    /**
     * Get diffs between two crawls.
     *
     * @param $timestamp1
     * @param $timestamp2
     */
    public function getCrawlDiffs($timestamp1, $timestamp2, $tolerance = 1000) {
        $queryString = sprintf("SELECT DISTINCT * FROM sites WHERE timestamp = '%s' order by url", $timestamp1);
        $results1 = $this->pdo->query($queryString);

        $queryString2 = sprintf("SELECT DISTINCT * FROM sites WHERE timestamp = '%s' order by url", $timestamp2);
        $results2 = $this->pdo->query($queryString2);

        $listofSites1 = array();

        while ($row = $results1->fetchArray()) {
            $listofSites1[$row['url']] = $row;
        }

        while ($row2 = $results2->fetchArray()) {
            $listofSites2[$row2[2]] = $row2;
            $index2 = $row2['url'];
            $diff = abs($listofSites1[$index2]['size'] - $row2['size']);
            if (($diff > $tolerance && $diff > 0) || ($listofSites1[$index2]['statusCode'] != $row2['statusCode'])) {
                echo 'nauthy';
                $naughtySite[$index2]['size1'][$index2] = $listofSites1[$index2]['size'];
                $naughtySite[$index2]['statusCode1'][$index2] = $listofSites1[$index2]['statusCode'];
                $naughtySite[$index2]['url1'][$index2] = $listofSites1[$index2]['url'];

                $naughtySite[$index2]['size2'][$row2[2]] = $row2['size'];
                $naughtySite[$index2]['statusCode2'][$row2[2]] = $row2['statusCode'];
                $naughtySite[$index2]['url2'][$index2] = $listofSites1[$index2]['url'];
            }
        }

        return $naughtySite;
    }

    /**
     * Get different crawls
     *
     * @return mixed
     */
    public function getStatsByStatus($statusCode) {

        $timeStamps = $this->getTimeStamps();
        foreach ($timeStamps as $timeStamp) {
            $queryString = sprintf("SELECT COUNT(*) as count FROM sites WHERE timestamp = '%s' AND statusCode = '%d'", $timeStamp, $statusCode);
            $rows = $this->pdo->query($queryString);
            $row = $rows->fetchArray();

            $numRows[$timeStamp] = $row['count'];
        }

        return $numRows;
    }

    public function addSitemapURL($url, $index, $timestamp)
    {
        // TODO: Implement addTemporaryURL() method.
        $query = sprintf("INSERT INTO sitemapURLs (timestamp, url, site_id) VALUES(%d,%d,\"%s\")", $timestamp, $index, $url);
        $this->pdo->query($query);
    }

    public function getSitemapURLs($dumpCurrent = FALSE) {
        $queryString = sprintf("SELECT * FROM sitemapURLs ");
        $query = $this->pdo->query($queryString);

        while ($row = $query->fetchArray()) {
            $results[] = $row;
        }

        if($dumpCurrent) {
            // Remove once has been accessed.
            $this->dumpSitemaps();
        }

        return $results;
    }

    public function dumpSitemaps() {
        $this->pdo->query("delete from sitemapURLs ");
    }


    public function addPendingURL($url, $index, $timestamp)
    {
        // TODO: Implement addTemporaryURL() method.
        $query = sprintf("INSERT INTO pendingURLs (timestamp, url, site_id) VALUES(%d,\"%s\",%d)", $timestamp, $url, $index);
        $this->pdo->query($query);
    }

    public function getPendingURLs($dumpCurrent = FALSE)
    {
        // TODO: Implement addTemporaryURL() method.
        $queryString = sprintf("SELECT url FROM pendingURLs");
        $query = $this->pdo->query($queryString);

        while ($row = $query->fetchArray()) {
            $results[] = $row['url'];
        }

        if ($dumpCurrent) {
            // Remove once has been accessed.
            $this->dumpPendingURLs();
        }

        return $results;
    }

    public function dumpPendingURLs() {
        $this->pdo->query("delete from pendingURLs");
    }
}
