-- Create user_xp table
CREATE TABLE IF NOT EXISTS user_xp (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    source VARCHAR(255) NOT NULL,
    date_earned DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create badges table
CREATE TABLE IF NOT EXISTS badges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    xp_required INT NOT NULL DEFAULT 0
);

-- Create user_badges table
CREATE TABLE IF NOT EXISTS user_badges (
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    date_earned DATETIME NOT NULL,
    PRIMARY KEY (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- Create tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT
);

-- Create user_tags table
CREATE TABLE IF NOT EXISTS user_tags (
    user_id INT NOT NULL,
    tag_id INT NOT NULL,
    date_assigned DATETIME NOT NULL,
    PRIMARY KEY (user_id, tag_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Create volunteer_hours table
CREATE TABLE IF NOT EXISTS volunteer_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hours DECIMAL(5,2) NOT NULL,
    activity VARCHAR(255) NOT NULL,
    date_recorded DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert some initial badges
INSERT INTO badges (name, description, xp_required) VALUES
('First Planting', 'Completed your first tree planting', 100),
('Tree Champion', 'Planted 10 or more trees', 500),
('Green Guardian', 'Completed 5 or more volunteer hours', 1000),
('Eco Warrior', 'Submitted 10 or more reports', 2000);

-- Insert some initial tags
INSERT INTO tags (name, description) VALUES
('Tree Planter', 'Active participant in tree planting activities'),
('Volunteer', 'Regular contributor to environmental causes'),
('Reporter', 'Active in reporting environmental issues'),
('Community Leader', 'Takes initiative in environmental projects'); 