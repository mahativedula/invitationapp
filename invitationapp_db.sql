CREATE DATABASE IF NOT EXISTS invitationapp_db;

DROP TABLE IF EXISTS messages CASCADE;
DROP TABLE IF EXISTS rsvps CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS users CASCADE;

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
    response VARCHAR(20) CHECK (response IN ('No Response', 'Going', 'Not Going', 'Maybe')) NOT NULL,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, recipient_id)
);

CREATE TABLE messages (
    message_id SERIAL PRIMARY KEY,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE,
    sender_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    recipient_id INT REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    subject VARCHAR(100) NOT NULL,
    content VARCHAR(5000) NOT NULL,
    viewed BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);