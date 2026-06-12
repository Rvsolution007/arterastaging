-- Rename tables
RENAME TABLE arterastaging.business_category TO arterastaging.category;
RENAME TABLE arterastaging.business_sub_category TO arterastaging.sub_category;

-- Rename columns in business table
ALTER TABLE arterastaging.business CHANGE business_category_id category_id INT;

-- Rename columns in business_frame table
ALTER TABLE arterastaging.business_frame CHANGE business_category_id category_id INT;
ALTER TABLE arterastaging.business_frame CHANGE business_sub_category_id sub_category_id INT;

-- Rename columns in sub_category table
ALTER TABLE arterastaging.sub_category CHANGE business_category_id category_id INT;

-- Rename columns in general_posts table
ALTER TABLE arterastaging.general_posts CHANGE business_category_id category_id INT;
ALTER TABLE arterastaging.general_posts CHANGE business_sub_category_id sub_category_id INT;

-- Rename columns in image_type_sub_category table
ALTER TABLE arterastaging.image_type_sub_category CHANGE business_sub_category_id sub_category_id INT;

-- Rename columns in video table
ALTER TABLE arterastaging.video CHANGE business_category_id category_id INT;
