SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `broadcast` (
  `id` int(11) UNSIGNED NOT NULL,
  `phone` varchar(25) NOT NULL,
  `status` enum('new','active','disabled') NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of numbers for the broadcast texts.';

CREATE TABLE `call_times` (
  `id` int(11) UNSIGNED NOT NULL,
  `contact_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `day` enum('all','weekdays','weekends','Sun','Mon','Tue','Wed','Thu','Fri','Sat') NOT NULL,
  `earliest` time NOT NULL,
  `latest` time NOT NULL,
  `receive_texts` enum('y','n') NOT NULL DEFAULT 'y',
  `language_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `enabled` enum('y','n') NOT NULL DEFAULT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Defines days and times that a volunteer will be called.';

CREATE TABLE `communications` (
  `id` int(11) UNSIGNED NOT NULL,
  `phone_from` varchar(25) NOT NULL,
  `phone_to` varchar(25) NOT NULL,
  `twilio_sid` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('text','call in progress','call answered','voicemail','call ended') DEFAULT NULL,
  `responded` datetime DEFAULT NULL,
  `communication_time` datetime NOT NULL,
  `media_urls` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `contacts` (
  `id` int(11) UNSIGNED NOT NULL,
  `contact_name` varchar(50) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `languages` (
  `id` int(11) UNSIGNED NOT NULL,
  `language` varchar(25) NOT NULL,
  `prompt` varchar(255) NOT NULL,
  `twilio_code` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Languages the hotline supports';

INSERT INTO `languages` (`id`, `language`, `prompt`, `twilio_code`) VALUES
(1, 'English', 'Press 1 for English.', 'en-US'),
(2, 'Spanish', 'Para espa&#241;ol oprima dos.', 'es-MX');


ALTER TABLE `broadcast`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

ALTER TABLE `call_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `language_id` (`language_id`);

ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `languages`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `call_times`
  ADD CONSTRAINT `call_times_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  ADD CONSTRAINT `call_times_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);

