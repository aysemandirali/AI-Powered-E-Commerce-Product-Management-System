<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'slug', 'description', 'parent_id', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get all active categories
     */
    public function getActiveCategories()
    {
        return $this->where('status', 1)->findAll();
    }

    /**
     * Get categories for dropdown
     */
    public function getMockCategories()
    {
        return $this->getActiveCategories();
    }

    /**
     * Generate unique slug
     */
    public function generateSlug($name, $id = null)
    {
        $slug = url_title($name, '-', true);
        $originalSlug = $slug;
        $counter = 1;

        do {
            $builder = $this->where('slug', $slug);
            if ($id) {
                $builder->where('id !=', $id);
            }
            $exists = $builder->countAllResults() > 0;

            if ($exists) {
                $slug = $originalSlug . '-' . $counter++;
            }
        } while ($exists);

        return $slug;
    }

    /**
     * Get category with parent information
     */
    public function getCategoryWithParent($id)
    {
        return $this->select('categories.*, parent.name as parent_name')
                   ->join('categories as parent', 'parent.id = categories.parent_id', 'left')
                   ->find($id);
    }

    /**
     * Get child categories
     */
    public function getChildCategories($parentId)
    {
        return $this->where('parent_id', $parentId)
                   ->where('status', 1)
                   ->findAll();
    }
}
