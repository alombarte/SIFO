<?php

namespace Sifo;

use Stash\Invalidation;
use Stash\Pool;

final class CacheClient implements CacheContract
{
    /** @var Pool */
    private $cache_pool;

    public function __construct(Pool $a_cache_pool)
    {
        $this->cache_pool = $a_cache_pool;
    }

    public function get($key)
    {
        if ($this->hasRebuild())
        {
            return false;
        }

        $item = $this->cache_pool->getItem($key);

        // When cache detects that other process is generating this same cache, it will
        // be serving the stored expired content until the cache generation ends.
        $item->setInvalidationMethod(Invalidation::OLD);
        if ( $item->isHit() )
        {
            return $item->get();
        }

        return false;
    }

    public function set($key, $content, $expiration)
    {
        $item = $this->cache_pool->getItem($key);
        $item->setInvalidationMethod(Invalidation::OLD);
        $item->lock();
        $item->set($content);
        $item->expiresAfter($expiration);
        $this->cache_pool->save($item);
    }

    public function delete($key)
    {
        $this->cache_pool->deleteItem($key);
    }

    public function deleteCacheByTag($tag, $value)
    {
        $value = Urls::normalize($value);
        $this->cache_pool->deleteItem("{$tag}/{$value}");
    }

    public function isActive()
    {
        return $this->cache_pool->getDriver()->isAvailable();
    }

    public function getCacheKeyName(array $definition)
    {
        if (empty($definition))
        {
            return false;
        }

		// Now we add the rest of identifiers of the definition excluding the "expiration".
		unset( $definition['expiration'] );

        $cache_config          = Config::getInstance()->getConfig('cache');
        $cache_tags_definition = (!empty($cache_config['cache_tags'])) ? $cache_config['cache_tags'] : [];

        $cache_keys = [];
        $cache_tags = '';
        foreach ( $definition as $tag => $value )
        {
            $value = Urls::normalize($value);
            if (in_array($tag, $cache_tags_definition))
            {
                $cache_tags = $tag . '/' . $value . '/';
            }
            else
            {
                $cache_keys[] = "{$tag}={$value}";
            }
        }
        sort( $cache_keys );
        array_unshift( $cache_keys, Domains::getInstance()->getDomain(), Domains::getInstance()->getLanguage() );

        $full_cache_key = $cache_tags . implode('_', $cache_keys);

        return $full_cache_key;
    }

    private function hasRebuild()
    {
		return Domains::getInstance()->getDevMode() && ( FilterGet::getInstance()->getInteger( 'rebuild' ) || FilterCookie::getInstance()->getInteger( 'rebuild_all' ) );
    }
}