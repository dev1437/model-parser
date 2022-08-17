<?php

namespace Dev1437\ModelParser\Tests;

use Dev1437\ModelParser\ModelParser;
use Dev1437\ModelParser\Models\Image;
use Dev1437\ModelParser\Models\Mechanic;
use Dev1437\ModelParser\Models\Project;
use Dev1437\ModelParser\Models\User;
use Dev1437\ModelParser\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Dev1437\ModelParser\Tests\TestCase;

class ModelParserTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new ModelParser(User::class);
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testGivenTheUserModelItReturnsTheModel()
    {
        $this->assertContains(User::class, $this->parser->parse());
    }

    public function testItReturnsTheFieldsForTheModel()
    {
        $this->assertArrayHasKey('fields', $this->parser->parse());
    }

    public function testTheFieldsContainAllDatabaseFieldsAsAnArray()
    {
        $this->assertArrayHasManyKeys(
            [
                'id',
                'name',
                'email',
                'email_verified_at',
                'password',
                'remember_token',
                'created_at',
                'updated_at',
            ],
            $this->parser->parse()['fields']
        );
    }

    public function testTheFieldsCanIgnoreHiddenFields()
    {
        $this->parser = new ModelParser(User::class, true);
        $this->assertArrayHasManyKeys(
            [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
            $this->parser->parse()['fields']
        );
    }

    public function testTheFieldsHaveTheirDataType()
    {
        $expected = [
            'id' => ['type' => 'bigint'],
            'name' => ['type' => 'string'],
            'email' => ['type' => 'string'],
            'email_verified_at' => ['type' => 'datetime'],
            'password' => ['type' => 'string'],
            'remember_token' => ['type' => 'string'],
            'created_at' => ['type' => 'datetime'],
            'updated_at' => ['type' => 'datetime'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['fields'][$key]);
        }
    }

    public function testTheFieldsShowIfTheyreNullable()
    {
        $expected = [
            'id' => ['nullable' => false],
            'name' => ['nullable' => false],
            'email' => ['nullable' => false],
            'email_verified_at' => ['nullable' => true],
            'password' => ['nullable' => false],
            'remember_token' => ['nullable' => true],
            'created_at' => ['nullable' => true],
            'updated_at' => ['nullable' => true],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['fields'][$key]);
        }
    }

    public function testItReturnsTheRelationsForTheModel()
    {
        $this->assertArrayHasKey('relations', $this->parser->parse());
    }

    public function testItReturnsAllRelationsForTheModel()
    {
        $this->assertArrayHasManyKeys(
            [
                'posts',
                'phone',
            ],
            $this->parser->parse()['relations']
        );
    }

    public function testTheRelationsHaveTheirType()
    {
        $expected = [
            'posts' => ['type' => 'HasMany'],
            'phone' => ['type' => 'HasOne'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testTheRelationsHaveTheirRelatedModel()
    {
        $expected = [
            'posts' => ['model' => 'Post'],
            'phone' => ['model' => 'Phone'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testHasManyRelationsHaveTheirKeys()
    {
        $expected = [
            'posts' => ['keys' => [
                'foreign_key' => 'user_id',
                'local_key' => 'id',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testHasOneRelationsHaveTheirKeys()
    {
        $expected = [
            'phone' => ['keys' => [
                'foreign_key' => 'user_id',
                'local_key' => 'id',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testBelongsToManyRelationsHaveTheirKeys()
    {
        $expected = [
            'accounts' => ['keys' => [
                'pivot_foreign_key' => 'user_id',
                'pivot_related_key' => 'account_id',
                'related_key' => 'id',
                'parent_key' => 'id',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testMorphOneRelationsHaveTheirKeys()
    {
        $expected = [
            'image' => ['keys' => [
                'foreign_key' => 'imageable_id',
                'local_key' => 'id',
                'morph_type' => 'imageable_type',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testItReturnsTheMutatorsForTheModel()
    {
        $this->assertArrayHasKey('mutators', $this->parser->parse());
    }

    public function testItReturnsAllMutatorsForTheModel()
    {
        $this->assertArrayHasManyKeys(
            [
                'pirate_name',
            ],
            $this->parser->parse()['mutators']
        );
    }

    public function testMutatorsHaveTheirType()
    {
        $expected = [
            'pirate_name' => ['type' => 'string'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['mutators'][$key]);
        }
    }

    public function testMutatorsShowIfTheyreNullable()
    {
        $expected = [
            'pirate_name' => ['nullable' => true],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['mutators'][$key]);
        }
    }

    public function testItReturnsTheCastsForTheModel()
    {
        $this->assertArrayHasKey('casts', $this->parser->parse());
    }

    public function testItReturnsAllCastsForTheModel()
    {
        $this->assertArrayHasManyKeys(
            [
                'id',
                'email_verified_at',
                'role',
            ],
            $this->parser->parse()['casts']
        );
    }

    public function testCastsHaveTheirType()
    {
        $expected = [
            'id' => ['type' => 'int'],
            'email_verified_at' => ['type' => 'datetime'],
            'role' => ['type' => 'Dev1437\ModelParser\Enums\UserRoleEnum'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['casts'][$key]);
        }
    }

    public function testCastsThatAreEnumsHaveTheirValues()
    {
        $expected = [
            'role' => ['values' => ['ADMIN' => 0, 'USER' => 1]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['casts'][$key]);
        }
    }

    public function testCastsHaveCastedAsAttribute()
    {
        $expected = [
            'role' => ['casted_as' => 'enum'],
            'email_verified_at' => ['casted_as' => 'class'],
            'name' => ['casted_as' => 'class'],
            'id' => ['casted_as' => 'primitive'],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['casts'][$key]);
        }
    }

    public function testCanFilterFieldsFromModel()
    {
        $this->parser = new ModelParser(User::class, false, [
            'email_verified_at',
        ]);
        $this->assertEquals(
            [
                'id',
                'name',
                'email',
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'role',
                'car_id',
            ],
            array_keys($this->parser->parse()['fields'])
        );
    }

    public function testMutatorsThatReturnAnEnumHaveTheEnumValues()
    {
        $expected = [
            'reserved_role' => ['enum' => [
                'BACKEND' => 0, 'FRONTEND' => 1,
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['mutators'][$key]);
        }
    }

    public function testBelongsToManyRelationshipsHaveTheirPivotTable()
    {
        $expected = [
            'accounts' => ['pivot' => [
                    'table' => 'account_user',
                    'columns' => [
                        'id' => [
                            'type' => 'integer',
                            'nullable' => true,
                        ],
                        'account_id' => [
                            'type' => 'integer',
                            'nullable' => true,
                        ],
                        'user_id' => [
                            'type' => 'integer',
                            'nullable' => true,
                        ],
                        'reason' => [
                            'type' => 'string',
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testMorphToRelationsHaveTheirKeys()
    {
        $this->parser = new ModelParser(Image::class);

        $expected = [
            'imageable' => ['keys' => [
                'foreign_key' => 'imageable_id',
                'morph_type' => 'imageable_type',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testMorphManyRelationsHaveTheirKeys()
    {
        $this->parser = new ModelParser(Video::class);

        $expected = [
            'comments' => ['keys' => [
                'foreign_key' => 'commentable_id',
                'local_key' => 'id',
                'morph_type' => 'commentable_type',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testMorphToManyRelationsHaveTheirKeys()
    {
        $this->parser = new ModelParser(Video::class);

        $expected = [
            'tags' => ['keys' => [
                'parent_key' => 'id',
                'related_key' => 'id',
                'pivot_foreign_key' => 'taggable_id',
                'pivot_related_key' => 'tag_id',
                'morph_type' => 'taggable_type',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testHasOneThroughRelationsHaveTheirKeys()
    {
        $this->parser = new ModelParser(Mechanic::class);

        $expected = [
            'carOwner' => ['keys' => [
                'first_key' => 'mechanic_id',
                'second_key' => 'id',
                'local_key' => 'id',
                'foreign_key' => 'car_id',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testMorphToManyRelationshipsHaveTheirPivotTable()
    {
        $this->parser = new ModelParser(Video::class);

        $expected = [
            'tags' => ['pivot' => [
                    'table' => 'taggables',
                    'columns' => [
                        'tag_id' => [
                            'type' => 'bigint',
                            'nullable' => true,
                        ],
                        'taggable_type' => [
                            'type' => 'string',
                            'nullable' => true,
                        ],
                        'taggable_id' => [
                            'type' => 'bigint',
                            'nullable' => true,
                        ],
                    ],
                ],
            ],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }

    public function testHasManyThroughRelationsHaveTheirKeys()
    {
        $this->parser = new ModelParser(Project::class);

        $expected = [
            'deployments' => ['keys' => [
                'first_key' => 'project_id',
                'second_key' => 'id',
                'local_key' => 'id',
                'foreign_key' => 'environment_id',
            ]],
        ];

        foreach ($expected as $key => $value) {
            $this->assertArraySubset($value, $this->parser->parse()['relations'][$key]);
        }
    }
}
