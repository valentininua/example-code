<?php

/**
 * Interface SwapRepository
 */
interface SwapRepository
{
    /**
     * @param $coin
     * @return mixed
     */
    public function getSwap($coin);
}

/**
 * Class swapConfigureCacheAdapter
 */
class swapConfigureCacheAdapter
{
    /**
     * @param array $config
     * @return mixed|void
     * @throws Exception
     */
    public function configure(array $config)
    {

        if (empty($type = $config['type'])) {
            return;
        }

        if (in_array($type, ['array', 'apcu', 'filesystem'], true)) {
            switch ($type) {
                case 'array':
                    $class = 'ArrayAdapter';
                    break;
                case 'apcu':
                    $class = 'ApcuAdapter';
                    break;
                case 'filesystem':
                    $class = 'FilesystemAdapter';
                    break;
                default:
                    throw new Exception("Unexpected swap cache type.");
            }

            if (!class_exists($class)) {
                throw new Exception("Cache class $class does not exist.");
            }

        } else {
            throw new Exception("Unexpected swap cache type.");
        }

        return new $class();

    }

}

/**
 * Class dbSwapRepository
 */
class dbSwapRepository implements SwapRepository
{
    /**
     * @param $coin
     * @return string
     */
    public function getSwap($coin)
    {
        return "SWAP  from DB";
    }
}

/**
 * Class httpSwapRepository
 */
class httpSwapRepository implements SwapRepository
{
    /**
     * @param $coin
     * @return string
     */
    public function getSwap($coin)
    {
        return "SWAP  from http";
    }
}

/**
 * Class swapRepositoryCachingDecorator
 */
class swapRepositoryCachingDecorator implements swapRepository
{
    /**
     * @var swapRepository
     */
    private $_decoratedRepository;
    /**
     * @var httpProductRepository
     */
    private $_httpProductRepository;
    /**
     * @var cache
     */
    private $_cache;
    /**
     * @var int
     */
    private $expirationInHours = 1;

    /**
     * swapRepositoryCachingDecorator constructor.
     * @param swapRepository $decoratedRepository
     * @param httpProductRepository $httpProductRepository
     * @param cache $cache
     */
    public function __construct(swapRepository $decoratedRepository, httpProductRepository $httpProductRepository, cache $cache)
    {
        $this->_decoratedRepository = $decoratedRepository;
        $this->_httpProductRepository = $httpProductRepository;
        $this->_cache = $cache;
    }

    /**
     * @param $coin
     * @return mixed
     */
    public function getSwap($coin)
    {
        $product = $this->_cache->get($coin);

        if ($product == null) {
            $product = $this->_decoratedRepository->getSwap($coin);
            if ($product == null)
                $this->_cache->set($coin, $product, (new dateTimeOffset)->now()->addHours($this->expirationInHours));
        }

        if ($product == null) {
            $product = $this->_httpProductRepository->getSwap($coin);
            if ($product == null)
                $this->_cache->set($coin, $product, (new dateTimeOffset)->now()->addHours($this->expirationInHours));
        }

        return product;
    }

}


interface cache
{
    /**
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param object $value
     * @param dateTimeOffset $expirationTime
     * @return mixed
     */
    public function set(string $key, object $value, dateTimeOffset $expirationTime);
}


$swapRepository = new swapRepositoryCachingDecorator(new dbProductRepository(), new httpProductRepository(), new cache());
echo $swap = $swapRepository->getSwap('RUB');






