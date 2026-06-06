-- Run this in your Supabase SQL Editor (Dashboard → SQL Editor → New Query)
ALTER TABLE users ALTER COLUMN profile_image TYPE TEXT;
ALTER TABLE messages ALTER COLUMN image_path TYPE TEXT;
ALTER TABLE messages ALTER COLUMN audio_path TYPE TEXT;
