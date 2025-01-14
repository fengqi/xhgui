<?php

namespace XHGui\Searcher;

use XHGui\Db\PdoRepository;
use XHGui\Exception\NotImplementedException;
use XHGui\Options\SearchOptions;
use XHGui\Profile;
use XHGui\Util;

class PdoSearcher implements SearcherInterface
{
    /** @var PdoRepository */
    private $db;

    public function __construct(PdoRepository $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function latest(): Profile
    {
        $row = $this->db->getLatest();

        return new Profile([
            '_id' => $row['id'],
            'meta' => [
                'url' => $row['url'],
                'SERVER' => json_decode($row['SERVER'], true),
                'get' => json_decode($row['GET'], true),
                'env' => json_decode($row['ENV'], true),
                'simple_url' => $row['simple_url'],
                'request_ts' => (int) $row['request_ts'],
                'request_ts_micro' => $row['request_ts_micro'],
                'request_date' => $row['request_date'],
            ],
            'profile' => json_decode($row['profile'], true),
        ]);
    }

    public function query($conditions, $limit, $fields = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id): Profile
    {
        $row = $this->db->getById($id);

        return new Profile([
            '_id' => $id,
            'meta' => [
                'url' => $row['url'],
                'SERVER' => json_decode($row['SERVER'], true),
                'get' => json_decode($row['GET'], true),
                'env' => json_decode($row['ENV'], true),
                'simple_url' => $row['simple_url'],
                'request_ts' => (int) $row['request_ts'],
                'request_ts_micro' => $row['request_ts_micro'],
                'request_date' => $row['request_date'],
            ],
            'profile' => json_decode($row['profile'], true),
        ]);
    }

    public function getForUrl($url, $options, $conditions = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
    }

    public function getPercentileForUrl($percentile, $url, $search = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvgsForUrl($url, $search = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(SearchOptions $options): array
    {
        $page = $options['page'];
        $direction = $options['direction'];
        $perPage = $options['perPage'];
        $url = $options['conditions']['url'] ?? '';

        $totalRows = $this->db->countByUrl($url);
        $totalPages = max(ceil($totalRows / $perPage), 1);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $skip = ($page - 1) * $perPage;

        $results = [];
        foreach ($this->db->findByUrl($url, $direction, $skip, $perPage) as $row) {
            $results[] = new Profile([
                '_id' => $row['id'],
                'meta' => [
                    'url' => $row['url'],
                    'SERVER' => json_decode($row['SERVER'], true),
                    'get' => json_decode($row['GET'], true),
                    'env' => json_decode($row['ENV'], true),
                    'simple_url' => $row['simple_url'],
                    'request_ts' => $row['request_ts'],
                    'request_ts_micro' => $row['request_ts_micro'],
                    'request_date' => $row['request_date'],
                ],
                'profile' => [
                    'main()' => [
                        'wt' => (int) $row['main_wt'],
                        'ct' => (int) $row['main_ct'],
                        'cpu' => (int) $row['main_cpu'],
                        'mu' => (int) $row['main_mu'],
                        'pmu' => (int) $row['main_pmu'],
                    ],
                ],
            ]);
        }

        return [
            'results' => $results,
            'sort' => 'meta.request_ts',
            'direction' => $direction,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): void
    {
        $this->db->deleteById($id);
    }

    public function truncate()
    {
        $this->db->deleteAll();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saveWatch(array $data): bool
    {
        if (empty($data['name'])) {
            return false;
        }

        if (!empty($data['removed']) && isset($data['_id'])) {
            $this->db->removeWatch($data['_id']);

            return true;
        }

        if (empty($data['_id'])) {
            $data['_id'] = Util::generateId();
            $data['removed'] = 0;
            $this->db->saveWatch($data);

            return true;
        }

        $this->db->updateWatch($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWatches(): array
    {
        $results = [];
        foreach ($this->db->getAllWatches() as $row) {
            $results[] = [
                '_id' => $row['id'],
                'removed' => $row['removed'],
                'name' => $row['name'],
            ];
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateWatches()
    {
        $this->db->truncateWatches();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function stats()
    {
        $row = $this->db->getStatistics();

        if (!$row) {
            $row = [
                'profiles' => 0,
                'latest' => 0,
                'bytes' => 0,
            ];
        }

        return $row;
    }
}
