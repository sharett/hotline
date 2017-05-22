
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `sanctuary`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_numbers`
--

CREATE TABLE `blocked_numbers` (
  `id` int(11) UNSIGNED NOT NULL,
  `phone` varchar(25) NOT NULL,
  `added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of blocked numbers.';


-- --------------------------------------------------------

--
-- Table structure for table `broadcast`
--

CREATE TABLE `broadcast` (
  `id` int(11) UNSIGNED NOT NULL,
  `phone` varchar(25) NOT NULL,
  `zipcode` varchar(5) NOT NULL DEFAULT '',
  `status` enum('new','active','disabled') NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of numbers for the broadcast texts.';

-- --------------------------------------------------------

--
-- Table structure for table `broadcast_responses`
--

CREATE TABLE `broadcast_responses` (
  `id` int(11) UNSIGNED NOT NULL,
  `broadcast_id` int(11) UNSIGNED NOT NULL,
  `communications_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Users that opted in to a broadcast campaign';

-- --------------------------------------------------------

--
-- Table structure for table `call_times`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) UNSIGNED NOT NULL,
  `contact_name` varchar(50) NOT NULL,
  `phone` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `errors`
--

CREATE TABLE `errors` (
  `id` int(11) UNSIGNED NOT NULL,
  `severity` enum('notice','warning','error','critical') NOT NULL DEFAULT 'error',
  `source` varchar(100) NOT NULL DEFAULT '' COMMENT 'PHP filename where the error occurred',
  `error_time` datetime NOT NULL,
  `admin_user` varchar(20) NOT NULL DEFAULT '',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) UNSIGNED NOT NULL,
  `language` varchar(25) NOT NULL,
  `keypress` int(1) UNSIGNED NOT NULL,
  `prompt` varchar(255) NOT NULL,
  `voicemail` varchar(255) NOT NULL DEFAULT '',
  `voicemail_received` varchar(255) NOT NULL DEFAULT '',
  `twilio_code` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Languages the hotline supports';


--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language`, `keypress`, `prompt`, `voicemail`, `voicemail_received`, `twilio_code`) VALUES
(1, 'English', 2, 'Press 2 for English.', 'No one is available to answer.  Please leave a message.', 'Your voicemail has been received.  Goodbye.', 'en-US'),
(2, 'Spanish', 1, 'Para espa&#241;ol oprima uno.', 'Nadie esta disponible. Favor de dejar un mensaje.', 'Tu correo de voz se ha recibido. Adi&oacute;s.', 'es-MX');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `zipcode` varchar(5) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(2) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_numbers`
--
ALTER TABLE `blocked_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `broadcast`
--
ALTER TABLE `broadcast`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `broadcast_responses`
--
ALTER TABLE `broadcast_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `call_times`
--
ALTER TABLE `call_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `communications`
--
ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `errors`
--
ALTER TABLE `errors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blocked_numbers`
--
ALTER TABLE `blocked_numbers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `broadcast`
--
ALTER TABLE `broadcast`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `broadcast_responses`
--
ALTER TABLE `broadcast_responses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `call_times`
--
ALTER TABLE `call_times`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `communications`
--
ALTER TABLE `communications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `errors`
--
ALTER TABLE `errors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `call_times`
--
ALTER TABLE `call_times`
  ADD CONSTRAINT `call_times_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`),
  ADD CONSTRAINT `call_times_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);

COMMIT;
