-- Run this in Hostinger phpMyAdmin if artisan migrate is not used.

ALTER TABLE `homework_submissions`
  ADD COLUMN `corrected_sheet_path` VARCHAR(255) NULL AFTER `pdf_path`;

ALTER TABLE `exam_submissions`
  ADD COLUMN `corrected_sheet_path` VARCHAR(255) NULL AFTER `pdf_path`;
