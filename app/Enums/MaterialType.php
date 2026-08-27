<?php
namespace App\Enums;

enum MaterialType: string
{
    case Pdf = 'pdf';
    case Ppt = 'ppt';
    case Doc = 'doc';
    case Xls = 'xls';
    case Zip = 'zip';
    case Image = 'image';
    case Link = 'link';
    case Video = 'video';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Pdf => 'PDF',
            self::Ppt => 'PowerPoint',
            self::Doc => 'Document',
            self::Xls => 'Spreadsheet',
            self::Zip => 'Archive',
            self::Image => 'Image',
            self::Link => 'Link',
            self::Video => 'Video',
            self::Other => 'Other',
        };
    }
}