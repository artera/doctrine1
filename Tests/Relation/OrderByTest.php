<?php

namespace Tests\Relation {
    use Tests\DoctrineUnitTestCase;

    class OrderByTest extends DoctrineUnitTestCase
    {
        protected static \Doctrine1\Connection\Profiler $profiler;
        protected static array $tables = [
            'OrderByTest_Article',
            'OrderByTest_Friend',
            'OrderByTest_Group',
            'OrderByTest_User',
            'OrderByTest_UserGroup',
            'OrderByTest_Category',
            'OrderByTest_BlogPost',
        ];

        public static function setUpBeforeClass(): void
        {
            parent::setUpBeforeClass();
            static::$profiler = new \Doctrine1\Connection\Profiler();
            static::$conn->addListener(static::$profiler);
        }

        public function testFullDqlQuery()
        {
            $userTable = \Doctrine1\Core::getTable('OrderByTest_User');
            $q         = $userTable
            ->createQuery('u')
            ->select('u.id')
            ->leftJoin('u.Articles a')
            ->leftJoin('u.Groups g')
            ->leftJoin('u.Friends f')
            ->leftJoin('u.ChildrenUsers cu')
            ->leftJoin('u.ParentUser pu');

            $this->assertEquals($q->getSqlQuery(), 'SELECT o.id AS o__id FROM order_by_test__user o LEFT JOIN order_by_test__article o2 ON o.id = o2.user_id LEFT JOIN order_by_test__user_group o4 ON (o.id = o4.user_id) LEFT JOIN order_by_test__group o3 ON o3.id = o4.group_id LEFT JOIN order_by_test__friend o6 ON (o.id = o6.user_id1 OR o.id = o6.user_id2) LEFT JOIN order_by_test__user o5 ON (o5.id = o6.user_id2 OR o5.id = o6.user_id1) AND o5.id != o.id LEFT JOIN order_by_test__user o7 ON o.id = o7.parent_user_id LEFT JOIN order_by_test__user o8 ON o.parent_user_id = o8.id ORDER BY o2.title ASC, o3.name ASC, o4.name ASC, o5.login ASC, o6.login ASC, o7.login ASC, o8.id ASC');
        }

        public function testLazyLoadingQueries()
        {
            $userTable = \Doctrine1\Core::getTable('OrderByTest_User');

            $this->assertEquals($userTable->getRelation('Articles')->getRelationDql(1), 'FROM OrderByTest_Article WHERE OrderByTest_Article.user_id IN (?) ORDER BY OrderByTest_Article.title ASC');
            $this->assertEquals($userTable->getRelation('Groups')->getRelationDql(1), 'FROM OrderByTest_Group.OrderByTest_UserGroup WHERE OrderByTest_Group.OrderByTest_UserGroup.user_id IN (?) ORDER BY OrderByTest_Group.name ASC');
            $this->assertEquals($userTable->getRelation('Friends')->getRelationDql(1), 'FROM OrderByTest_User.OrderByTest_Friend WHERE OrderByTest_User.OrderByTest_Friend.user_id1 IN (?) ORDER BY OrderByTest_User.username ASC');
            $this->assertEquals($userTable->getRelation('ParentUser')->getRelationDql(1), 'FROM OrderByTest_User WHERE OrderByTest_User.id IN (?) ORDER BY OrderByTest_User.id ASC');
            $this->assertEquals($userTable->getRelation('ChildrenUsers')->getRelationDql(1), 'FROM OrderByTest_User WHERE OrderByTest_User.parent_user_id IN (?) ORDER BY OrderByTest_User.username ASC');

            $user           = new \OrderByTest_User();
            $user->username = 'jwage';
            $user->password = 'changeme';

            $user2            = new \OrderByTest_User();
            $user2->username  = 'parent';
            $user2->password  = 'changeme';
            $user->ParentUser = $user2;
            $user->save();

            $articles = $user->Articles;
            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT o.id AS o__id, o.title AS o__title, o.content AS o__content, o.user_id AS o__user_id FROM order_by_test__article o WHERE (o.user_id IN (?)) ORDER BY o.title ASC');

            $groups = $user->Groups;
            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT o.id AS o__id, o.name AS o__name, o2.user_id AS o2__user_id, o2.group_id AS o2__group_id FROM order_by_test__group o LEFT JOIN order_by_test__user_group o2 ON o.id = o2.group_id WHERE (o2.user_id IN (?)) ORDER BY o.name ASC');

            $friends = $user->Friends;
            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT order_by_test__user.id AS order_by_test__user__id, order_by_test__user.login AS order_by_test__user__login, order_by_test__user.password AS order_by_test__user__password, order_by_test__user.parent_user_id AS order_by_test__user__parent_user_id, order_by_test__friend.user_id1 AS order_by_test__friend__user_id1, order_by_test__friend.user_id2 AS order_by_test__friend__user_id2 FROM order_by_test__user INNER JOIN order_by_test__friend ON order_by_test__user.id = order_by_test__friend.user_id2 OR order_by_test__user.id = order_by_test__friend.user_id1 WHERE order_by_test__user.id IN (SELECT user_id2 FROM order_by_test__friend WHERE user_id1 = ?) OR order_by_test__user.id IN (SELECT user_id1 FROM order_by_test__friend WHERE user_id2 = ?) ORDER BY order_by_test__user.login ASC');

            $childrenUsers = $user->ChildrenUsers;
            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT o.id AS o__id, o.login AS o__login, o.password AS o__password, o.parent_user_id AS o__parent_user_id FROM order_by_test__user o WHERE (o.parent_user_id IN (?)) ORDER BY o.login ASC');

            $parentUser = $user->ParentUser;
            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT o.id AS o__id, o.login AS o__login, o.password AS o__password, o.parent_user_id AS o__parent_user_id FROM order_by_test__user o WHERE (o.parent_user_id IN (?)) ORDER BY o.login ASC');
        }

        public function testMasterOrderBy()
        {
            $q = \Doctrine1\Core::getTable('OrderByTest_Category')
            ->createQuery('c')
            ->select('c.id, p.id')
            ->leftJoin('c.Posts p');

            $this->assertEquals($q->getSqlQuery(), 'SELECT o.id AS o__id, o2.id AS o2__id FROM order_by_test__category o LEFT JOIN order_by_test__blog_post o2 ON o.id = o2.category_id ORDER BY o.name ASC, o2.title ASC, o2.is_first DESC');

            $category       = new \OrderByTest_Category();
            $category->name = 'php';
            $category->save();

            $posts = $category->Posts;

            $this->assertEquals(static::$profiler->pop()->getQuery(), 'SELECT o.id AS o__id, o.title AS o__title, o.is_first AS o__is_first, o.category_id AS o__category_id FROM order_by_test__blog_post o WHERE (o.category_id IN (?)) ORDER BY o.title ASC, o.is_first DESC');
        }

        public function testOrderByFromQueryComesFirst()
        {
            $q = \Doctrine1\Core::getTable('OrderByTest_Category')
            ->createQuery('c')
            ->select('c.id, p.id')
            ->leftJoin('c.Posts p')
            ->orderBy('p.title ASC');

            $this->assertEquals($q->getSqlQuery(), 'SELECT o.id AS o__id, o2.id AS o2__id FROM order_by_test__category o LEFT JOIN order_by_test__blog_post o2 ON o.id = o2.category_id ORDER BY o2.title ASC, o.name ASC, o2.is_first DESC');
        }

        public function testWeirdSort()
        {
            $q = \Doctrine1\Core::getTable('OrderByTest_WeirdSort')
            ->createQuery('w')
            ->select('w.id');

            $this->assertEquals($q->getSqlQuery(), 'SELECT o.id AS o__id FROM order_by_test__weird_sort o ORDER BY RAND()');
        }
    }
}

namespace {
    class OrderByTest_Article extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('title', 'string', 255);
            $this->hasColumn('content', 'string');
            $this->hasColumn('user_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'OrderByTest_User as User',
                [
                'local'   => 'user_id',
                'foreign' => 'id']
            );
        }
    }

    class OrderByTest_Friend extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'user_id1',
                'integer',
                null,
                [
                'primary' => true,
                ]
            );
            $this->hasColumn(
                'user_id2',
                'integer',
                null,
                [
                'primary' => true,
                ]
            );
        }
    }

    class OrderByTest_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'type'   => 'string',
                'length' => '255',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasMany(
                'OrderByTest_User as User',
                [
                'refClass' => 'OrderByTest_UserGroup',
                'local'    => 'group_id',
                'foreign'  => 'user_id']
            );
        }
    }

    class OrderByTest_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('login AS username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('parent_user_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasMany(
                'OrderByTest_Article as Articles',
                [
                'local'   => 'id',
                'foreign' => 'user_id',
                'orderBy' => 'title ASC']
            );

            $this->hasMany(
                'OrderByTest_Group as Groups',
                [
                'refClass' => 'OrderByTest_UserGroup',
                'local'    => 'user_id',
                'foreign'  => 'group_id',
                'orderBy'  => 'name ASC']
            );

            $this->hasMany(
                'OrderByTest_User as Friends',
                [
                'refClass' => 'OrderByTest_Friend',
                'local'    => 'user_id1',
                'foreign'  => 'user_id2',
                'equal'    => true,
                'orderBy'  => 'username ASC']
            );

            $this->hasOne(
                'OrderByTest_User as ParentUser',
                [
                'local'   => 'parent_user_id',
                'foreign' => 'id',
                'orderBy' => 'id ASC']
            );

            $this->hasMany(
                'OrderByTest_User as ChildrenUsers',
                [
                'local'   => 'id',
                'foreign' => 'parent_user_id',
                'orderBy' => 'username ASC']
            );
        }
    }

    class OrderByTest_UserGroup extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'user_id',
                'integer',
                null,
                [
                'primary' => true,
                ]
            );
            $this->hasColumn(
                'group_id',
                'integer',
                null,
                [
                'primary' => true,
                ]
            );
        }
    }


    class OrderByTest_Category extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->getTable()->orderBy = 'name ASC';

            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'OrderByTest_BlogPost as Posts',
                [
                'local'   => 'id',
                'foreign' => 'category_id'
                ]
            );
        }
    }

    class OrderByTest_BlogPost extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->getTable()->orderBy = 'title ASC, is_first DESC';

            $this->hasColumn('title', 'string', 255);
            $this->hasColumn('is_first', 'boolean');
            $this->hasColumn('category_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'OrderByTest_Category',
                [
                'local'   => 'category_id',
                'foreign' => 'id'
                ]
            );
        }
    }

    class OrderByTest_WeirdSort extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->getTable()->orderBy = 'RAND()';

            $this->hasColumn('title', 'string', 255);
        }
    }
}
