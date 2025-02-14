<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    /** @var Setting[] */
    private ?array $cache = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getValue(string $code): ?string
    {
        $this->loadToCache();

        if (isset($this->cache[$code])) {
            return $this->cache[$code]->getValue();
        }

        return null;
    }

    public function setValue(string $code, mixed $value): void
    {
        $this->loadToCache();

        if (isset($this->cache[$code])) {
            $this->cache[$code]->setValue($value);
        } else {
            $setting = new Setting($code, $value);
            $this->cache[$code] = $setting;

            $this->getEntityManager()->persist($setting);
        }

        $this->getEntityManager()->flush();
    }

    private function loadToCache(): void
    {
        if ($this->cache !== null) {
            return;
        }

        foreach ($this->findAll() as $setting) {
            $this->cache[$setting->getCode()] = $setting;
        }
    }
}
