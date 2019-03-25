<?php

namespace RebelCode\Wpra\Core\Data\Collections;

use ArrayAccess;
use ArrayIterator;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use InvalidArgumentException;
use OutOfRangeException;
use RebelCode\Wpra\Core\Data\AbstractDataSet;
use RebelCode\Wpra\Core\Data\DataSetInterface;
use RebelCode\Wpra\Core\Data\Wp\WpPostDataSet;
use RuntimeException;
use stdClass;
use Traversable;
use WP_Error;
use WP_Post;

/**
 * A data set implementation that acts as a wrapper for a collection of posts.
 *
 * @since [*next-version*]
 */
class WpPostCollection extends AbstractDataSet implements CollectionInterface
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The post type.
     *
     * @since [*next-version*]
     *
     * @var string
     */
    protected $postType;

    /**
     * The meta query.
     *
     * @since [*next-version*]
     *
     * @var array
     */
    protected $metaQuery;

    /**
     * The ID of the last inserted post.
     *
     * @since [*next-version*]
     *
     * @var int|string
     */
    protected $lastInsertedId;

    /**
     * Optional filter to restrict the collection query.
     *
     * @since [*next-version*]
     *
     * @var array|null
     */
    protected $filter;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string     $postType  The post type.
     * @param array      $metaQuery The meta query.
     * @param array|null $filter    Optional filter to restrict the collection query.
     */
    public function __construct($postType, $metaQuery = [], $filter = null)
    {
        $this->postType = $postType;
        $this->metaQuery = $metaQuery;
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function get($key)
    {
        if ($key === null && $this->lastInsertedId !== null) {
            return $this->offsetGet($this->lastInsertedId);
        }

        $posts = $this->queryPosts($key);

        if (count($posts) === 0) {
            throw new OutOfRangeException(
                sprintf(__('Post "%s" was not found', 'wprss'), $key)
            );
        }

        $post = reset($posts);
        $result = $this->createModel($post);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function has($key)
    {
        if ($key === null) {
            return false;
        }

        $posts = $this->queryPosts($key);

        return count($posts) === 1;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function set($key, $data)
    {
        if ($key === null) {
            $this->createPost($data);

            return;
        }

        $this->updatePost($key, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function delete($key)
    {
        wp_delete_post($key, true);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function filter($filter)
    {
        if (!is_array($filter)) {
            throw new InvalidArgumentException('Collection filter argument is not an array');
        }

        if (empty($filter)) {
            return $this;
        }

        $currFilter = empty($this->filter) ? [] : $this->filter;
        $newFilter = array_merge($currFilter, $filter);

        return $this->createSelfWithFilter($newFilter);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getCount()
    {
        return count($this->queryPosts(null));
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function clear()
    {
        foreach ($this->getIterator() as $post) {
            $this->delete($post->ID);
        }
    }

    /**
     * Creates a new post using the given data.
     *
     * @since [*next-version*]
     *
     * @param array $data The data to create the post with.
     */
    protected function createPost($data)
    {
        $post = $this->getNewPostData($data);
        $result = wp_insert_post($post, true);

        if ($result instanceof WP_Error) {
            throw new RuntimeException($result->get_error_message(), $result->get_error_code());
        }

        $this->lastInsertedId = $result;
        $this->updatePost($result, $data);
    }

    /**
     * Updates a post.
     *
     * @since [*next-version*]
     *
     * @param int|string $key  The post's key (ID or slug).
     * @param array      $data The data to update the post with.
     */
    protected function updatePost($key, $data)
    {
        $post = $this->get($key);
        $data = $this->getUpdatePostData($key, $data);

        foreach ($data as $k => $v) {
            $post[$k] = $v;
        }
    }

    /**
     * Retrieves the data to use for creating a new post.
     *
     * @since [*next-version*]
     *
     * @param array $data The data being used to create the post.
     *
     * @return array The actual data to use with {@link wp_insert_post}.
     */
    protected function getNewPostData($data)
    {
        return [
            'post_type' => $this->postType,
        ];
    }

    /**
     * Retrieves the data to use for updating a post.
     *
     * @since [*next-version*]
     *
     * @param int|string $key  The post key (ID or slug).
     * @param array      $data The data being used to update the post.
     *
     * @return array The actual data to update the post with.
     */
    protected function getUpdatePostData($key, $data)
    {
        return $data;
    }

    /**
     * Normalizes a variable into a post array,
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable|WP_Post $post Post data array, object or iterable, or a WP_Post instance.
     *
     * @return array The post data array.
     */
    protected function toPostArray($post)
    {
        if ($post instanceof WP_Post) {
            return $post->to_array();
        }

        return $this->_normalizeArray($post);
    }

    /**
     * Recursively patches a subject with every entry in a given patch data array.
     *
     * @since [*next-version*]
     *
     * @param array|ArrayAccess          $subject The subject to patch.
     * @param array|stdClass|Traversable $patch   The data to patch the subject with.
     *
     * @return array|ArrayAccess The patched subject.
     */
    protected function recursivePatch($subject, $patch)
    {
        foreach ($patch as $key => $value) {
            $subject[$key] = $value;
        }

        return $subject;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function getIterator()
    {
        return new ArrayIterator($this->queryPosts(null));
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function recursiveUnpackIterators()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    protected function createIterationValue($value)
    {
        return $this->createModel($value);
    }

    /**
     * Creates the resulting dataset model.
     *
     * @since [*next-version*]
     *
     * @param WP_Post $post The post.
     *
     * @return DataSetInterface The dataset model.
     */
    protected function createModel(WP_Post $post)
    {
        return new WpPostDataSet($post);
    }

    /**
     * Queries the posts.
     *
     * @since [*next-version*]
     *
     * @param int|string|null $key Optional ID or slug which, if not null, narrows down the query to only that post.
     *
     * @return WP_Post[] An array of posts objects.
     */
    protected function queryPosts($key = null)
    {
        $queryArgs = [
            'post_type' => $this->postType,
            'suppress_filters' => true,
            'cache_results' => false,
            'posts_per_page' => -1,
            'meta_query' => $this->metaQuery,
        ];

        if ($key !== null && is_numeric($key)) {
            $queryArgs['p'] = $key;
        }

        if ($key !== null && is_string($key) && !is_numeric($key)) {
            $queryArgs['name'] = $key;
        }

        $filter = is_array($this->filter) ? $this->filter : [];

        foreach ($filter as $fKey => $fVal) {
            $handled = $this->handleFilter($queryArgs, $fKey, $fVal);

            if (!$handled) {
                $queryArgs[$fKey] = $fVal;
            }
        }

        return get_posts($queryArgs);
    }

    /**
     * Handles the processing of a filter.
     *
     * @since [*next-version*]
     *
     * @param array  $queryArgs The query arguments to modify, passed by reference.
     * @param string $key       The filter key.
     * @param mixed  $value     The filter value.
     *
     * @return bool True if the filter was handled, false if it wasn't.
     */
    protected function handleFilter(&$queryArgs, $key, $value)
    {
        if ($key === 'id') {
            $queryArgs['post__in'] = is_array($value) ? $value : [$value];

            return true;
        }

        if ($key === 's') {
            $queryArgs['s'] = $value;

            return true;
        }

        return false;
    }

    /**
     * Creates a new collection of this type with an added filter.
     *
     * @since [*next-version*]
     *
     * @param array $filter The filter for restricting the collection query.
     *
     * @return CollectionInterface
     */
    protected function createSelfWithFilter($filter)
    {
        return new static($this->postType, $this->metaQuery, $filter);
    }
}
