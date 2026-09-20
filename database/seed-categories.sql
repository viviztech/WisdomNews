USE `wisdomnews`;

INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `status`, `sort_order`) VALUES
  (NULL, 'செய்திகள்', 'seithigal', 'பொதுச் செய்திகள் மற்றும் தினசரி நிகழ்வுகள்', 'active', 10),
  (NULL, 'முக்கிய செய்திகள்', 'mukkiya-seithigal', 'முக்கியமான சமீபத்திய செய்திகள்', 'active', 20),
  (NULL, 'நிஜங்கள்', 'nijangal', 'உண்மை, அறிவியல் மற்றும் வாழ்வியல் பார்வைகள்', 'active', 30),
  (NULL, 'புதிய அரசியல் பார்வை', 'puthiya-arasiyal-paarvai', 'அரசியல் செய்திகள் மற்றும் ஆழமான பார்வைகள்', 'active', 40),
  (NULL, 'மனித ஆற்றல்', 'manitha-aatral', 'மனித ஆற்றல் மற்றும் முன்னேற்றம்', 'active', 50),
  (NULL, 'முன்னேற்ற பயணம்', 'munnetra-payanam', 'முன்னேற்றம் தரும் செய்திகள் மற்றும் கதைகள்', 'active', 60),
  (NULL, 'Wisdom Special', 'wisdom-special', 'Wisdom News சிறப்புக் கட்டுரைகள்', 'active', 70)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `status` = VALUES(`status`),
  `sort_order` = VALUES(`sort_order`);

SET @wisdom_special_id = (SELECT `id` FROM `categories` WHERE `slug` = 'wisdom-special' LIMIT 1);

INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `status`, `sort_order`) VALUES
  (@wisdom_special_id, 'நிழல் to நிஜம்', 'nizhal-to-nijam', 'நிழலிலிருந்து நிஜத்தை அறியும் சிறப்புக் கட்டுரைகள்', 'active', 71),
  (@wisdom_special_id, 'பிரபஞ்ச ரகசியம்', 'prabancha-ragasiyam', 'பிரபஞ்சம் மற்றும் அதன் ரகசியங்கள்', 'active', 72),
  (@wisdom_special_id, 'மனித தந்திரம்', 'manitha-thanthiram', 'மனித மனம் மற்றும் செயல்பாடுகள்', 'active', 73),
  (@wisdom_special_id, 'முன்னேற்றும் கதைகள்', 'munnetrum-kathaigal', 'வாழ்க்கையை முன்னேற்றும் கதைகள்', 'active', 74)
ON DUPLICATE KEY UPDATE
  `parent_id` = VALUES(`parent_id`),
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `status` = VALUES(`status`),
  `sort_order` = VALUES(`sort_order`);

UPDATE `settings`
SET `setting_value` = 'உண்மையை அறிந்து, அறிவுடன் முன்னேறுவோம்.'
WHERE `setting_key` = 'footer_text'
  AND (`setting_value` = '' OR `setting_value` LIKE 'Ó%');
