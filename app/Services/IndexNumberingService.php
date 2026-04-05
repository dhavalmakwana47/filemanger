<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\File;

class IndexNumberingService
{
    /**
     * Last numeric segment of a dotted index (e.g. "1.12" -> 12, "5" -> 5).
     * Used instead of SQL MAX(item_index), which compares strings ("9" > "10").
     */
    public static function lastSegmentAsInt(?string $itemIndex): int
    {
        if ($itemIndex === null || $itemIndex === '') {
            return 0;
        }
        $segments = explode('.', trim((string) $itemIndex));

        return (int) end($segments);
    }

    /**
     * Highest last-segment value among root-level folders and files for a company.
     */
    private static function maxNumericRootOrdinal(int $companyId): int
    {
        $max = 0;
        foreach (Folder::where('company_id', $companyId)->whereNull('parent_id')->pluck('item_index') as $idx) {
            $max = max($max, self::lastSegmentAsInt($idx));
        }
        foreach (File::where('company_id', $companyId)->whereNull('folder_id')->pluck('item_index') as $idx) {
            $max = max($max, self::lastSegmentAsInt($idx));
        }

        return $max;
    }

    /**
     * Highest direct-child ordinal under a parent index (e.g. parent "122.4" → use 7 from "122.4.7", 12 from "122.4.12.1").
     * Ignores legacy rows like "122.9" that do not start with "122.4." so they are not mistaken for child "9".
     */
    private static function maxDirectChildOrdinalUnderParent(string $parentIndex, int $companyId, int $parentId): int
    {
        $parentIndex = self::normalizeIndex($parentIndex);
        if ($parentIndex === '') {
            return 0;
        }
        $prefix = $parentIndex . '.';
        $len = strlen($prefix);
        $max = 0;

        foreach (Folder::where('company_id', $companyId)->where('parent_id', $parentId)->pluck('item_index') as $idx) {
            $norm = self::normalizeIndex((string) $idx);
            if ($norm === '' || $norm === $parentIndex) {
                continue;
            }
            if (str_starts_with($norm, $prefix)) {
                $rest = substr($norm, $len);
                if ($rest === '') {
                    continue;
                }
                $first = (int) explode('.', $rest)[0];
                $max = max($max, $first);
            }
        }
        foreach (File::where('company_id', $companyId)->where('folder_id', $parentId)->pluck('item_index') as $idx) {
            $norm = self::normalizeIndex((string) $idx);
            if ($norm === '' || $norm === $parentIndex) {
                continue;
            }
            if (str_starts_with($norm, $prefix)) {
                $rest = substr($norm, $len);
                if ($rest === '') {
                    continue;
                }
                $first = (int) explode('.', $rest)[0];
                $max = max($max, $first);
            }
        }

        return $max;
    }

    /**
     * Generate next index number for a folder or file
     * 
     * @param int|null $parentId
     * @param string $type 'folder' or 'file'
     * @param string|null $customParentIndex Custom parent index if provided
     * @return string
     */
    public static function generateNextIndex($parentId = null, $type = 'folder', $customParentIndex = null)
    {
        $companyId = get_active_company();
        
        // If custom parent index is provided, normalize it
        if ($customParentIndex !== null && $parentId === null) {
            return self::normalizeIndex($customParentIndex);
        }
        
        if ($parentId === null) {
            // Root level: numeric max of root indices (not SQL MAX on string — "9" would beat "10")
            $next = self::maxNumericRootOrdinal($companyId) + 1;

            return (string) $next;
        }
        
        // Get parent's index
        $parent = Folder::find($parentId);
        if (!$parent) {
            return '1';
        }
        
        $parentIndex = $customParentIndex ?? ($parent->item_index ?: '1');
        $parentIndex = self::normalizeIndex($parentIndex);
        
        $maxOrdinal = self::maxDirectChildOrdinalUnderParent($parentIndex, $companyId, $parentId);
        
        return $parentIndex . '.' . ($maxOrdinal + 1);
    }
    
    /**
     * Normalize index by removing leading zeros from each segment
     * Example: "1.08.03" becomes "1.8.3"
     * 
     * @param string|null $index
     * @return string
     */
    public static function normalizeIndex($index)
    {
        if (empty($index)) {
            return '';
        }
        
        $parts = explode('.', $index);
        $normalized = array_map(function($part) {
            return (string)((int)trim($part));
        }, $parts);
        
        return implode('.', $normalized);
    }
    
    /**
     * Reindex all items in a folder (after move or delete)
     * 
     * @param int|null $parentId
     * @return void
     */
    public static function reindexFolder($parentId = null)
    {
        $companyId = get_active_company();
        
        if ($parentId === null) {
            // Reindex root level
            $folders = Folder::where('company_id', $companyId)
                ->whereNull('parent_id')
                ->orderBy('item_index')
                ->get();
            
            $files = File::where('company_id', $companyId)
                ->whereNull('folder_id')
                ->orderBy('item_index')
                ->get();
            
            $index = 1;
            foreach ($folders as $folder) {
                $folder->update(['item_index' => (string)$index]);
                self::reindexChildren($folder->id, (string)$index);
                $index++;
            }
            
            foreach ($files as $file) {
                $file->update(['item_index' => (string)$index]);
                $index++;
            }
        } else {
            self::reindexChildren($parentId);
        }
    }
    
    /**
     * Reindex children of a folder
     * 
     * @param int $parentId
     * @param string|null $parentIndex
     * @return void
     */
    private static function reindexChildren($parentId, $parentIndex = null)
    {
        $companyId = get_active_company();
        
        if ($parentIndex === null) {
            $parent = Folder::find($parentId);
            $parentIndex = $parent ? $parent->item_index : '1';
        }
        
        $folders = Folder::where('company_id', $companyId)
            ->where('parent_id', $parentId)
            ->orderBy('item_index')
            ->get();
        
        $files = File::where('company_id', $companyId)
            ->where('folder_id', $parentId)
            ->orderBy('item_index')
            ->get();
        
        $index = 1;
        foreach ($folders as $folder) {
            $newIndex = $parentIndex . '.' . $index;
            $folder->update(['item_index' => $newIndex]);
            self::reindexChildren($folder->id, $newIndex);
            $index++;
        }
        
        foreach ($files as $file) {
            $newIndex = $parentIndex . '.' . $index;
            $file->update(['item_index' => $newIndex]);
            $index++;
        }
    }
}
