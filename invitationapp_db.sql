DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS rsvps;
DROP TABLE IF EXISTS messages;

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    first_name VARCHAR(20) NOT NULL,
    last_name VARCHAR(20) NOT NULL,
    username VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    event_id SERIAL PRIMARY KEY,
    host_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    event_name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rsvps (
    rsvp_id SERIAL PRIMARY KEY,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    recipient_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    response VARCHAR(10) CHECK (response IN ('Going', 'Not Going', 'Maybe')) NOT NULL,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, user_id)
);

CREATE TABLE messages (
    message_id SERIAL PRIMARY KEY,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    sender_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    recipient_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    subject VARCHAR(100) NOT NULL,
    content TEXT(5000) NOT NULL,
    viewed BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);