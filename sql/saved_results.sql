-- Create saved_results table to save results

CREATE TABLE `saved_results` (
  id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT unsigned NOT NULL,
  title VARCHAR(250) NOT NULL,
  link VARCHAR(250) NOT NULL,
  description VARCHAR(250) NOT NULL,
  comment TEXT NOT NULL
);