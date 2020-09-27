<?php

namespace Owlcoder\OwlOrm\Tests\Models;

/**
 * @Column(id)
 * @TableName(user)
 *
 * Class User
 */
class User extends BaseModel
{
    public $_table = 'tbl_person';

    public function getRoles()
    {
        return $this->hasMany(UserRole::class, 'person_id');
    }
}