-- migration_base64_v2.sql
-- Run this in your Supabase SQL Editor

-- 1. Alter groups table to support Base64 images
ALTER TABLE public.groups 
ALTER COLUMN group_image TYPE TEXT;

-- 2. Ensure all other media columns are TEXT (Safety check)
ALTER TABLE public.users 
ALTER COLUMN profile_image TYPE TEXT;

ALTER TABLE public.messages 
ALTER COLUMN image_path TYPE TEXT,
ALTER COLUMN audio_path TYPE TEXT;

-- 3. (Optional) Verify current column types
-- SELECT table_name, column_name, data_type 
-- FROM information_schema.columns 
-- WHERE table_name IN ('groups', 'users', 'messages') 
-- AND column_name IN ('group_image', 'profile_image', 'image_path', 'audio_path');
