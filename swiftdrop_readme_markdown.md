# SwiftDrop – Flash Sale E-Commerce Platform

## 🚀 Overview

SwiftDrop is a high-performance flash sale e-commerce platform designed to handle massive concurrent purchase requests efficiently while preventing overselling and maintaining system stability.

The platform demonstrates how a properly architected PHP/MySQL system can reliably support high-traffic flash sale scenarios using transaction locking, Redis synchronization, queues, and real-time updates.

---

## 👨‍💻 Team

Developed by **Predictra – Sabaragamuwa University of Sri Lanka**

- Sankajith
- Sanodaya
- Kugashanth

---

# 📌 Problem Statement

Traditional e-commerce systems often fail during flash sales because of:

- Thousands of simultaneous purchase attempts
- Server overloads and crashes
- Stock overselling
- Duplicate transactions
- Poor real-time synchronization

SwiftDrop was built to solve these issues with strict concurrency control and optimized backend architecture.

---

# ✨ Features

## 🛒 Customer Features

- Secure user authentication
- Flash sale marketplace
- Countdown timers
- Real-time stock updates
- Order history
- Instant purchase confirmation

## ⚡ Flash Sale Features

- High concurrency handling
- Real-time inventory synchronization
- Zero overselling guarantee
- Queue-based order processing

## 🛠️ Admin Features

- Product management
- Event management
- Inventory control
- Revenue monitoring dashboard

---

# 🎯 Objectives

## Functional Goals

- Secure authentication system
- Dynamic product and event management
- One-click purchase workflow
- Real-time stock dashboard

## Non-Functional Goals

- Handle massive burst traffic
- Maintain data consistency
- Sub-second response time
- Scalable architecture
- Secure transaction handling

---

# 🏗️ System Architecture

SwiftDrop follows a **3-Tier Architecture**:

## 1. UI Layer

- Browser-based responsive interface
- AJAX-powered updates
- Non-blocking user experience

## 2. Logic Layer

- PHP + Apache backend
- Request validation
- Purchase processing
- Concurrency management

## 3. Data Layer

- MySQL with InnoDB engine
- Row-level locking
- Atomic transactions
- Persistent order storage

---

# 🔄 Flash Sale Processing Flow

```text
User clicks Buy
        ↓
Redis stock check
        ↓
Rate limit validation
        ↓
Request added to queue
        ↓
Database transaction begins
        ↓
SELECT FOR UPDATE lock
        ↓
Stock deducted safely
        ↓
Order record created
        ↓
Queue worker confirms order
        ↓
Success / Failure response
```

---

# 🗄️ Database Design

| Table Name  | Primary Key | Purpose |
|-------------|-------------|----------|
| users | user_id | User authentication and profile data |
| products | product_id | Product inventory and pricing |
| sale_events | event_id | Flash sale event management |
| orders | order_id | Transaction records |
| inventory | inv_id | Stock tracking and locking |

---

# 🔥 High Concurrency Handling

## The Challenge

When multiple users attempt to purchase the last remaining item simultaneously, race conditions can occur.

Example:

- Stock = 1
- Two users attempt checkout at the same time
- Both see stock available
- Result → Overselling

## The Solution

SwiftDrop prevents overselling using:

- `SELECT ... FOR UPDATE`
- Atomic database transactions
- Redis stock synchronization
- Queue-based processing
- Rate limiting
- Row-level locking

---

# ⚡ Real-Time Updates

SwiftDrop uses optimized AJAX polling to:

- Update stock counts instantly
- Refresh countdown timers
- Prevent ghost stock purchases
- Improve user experience without page reloads

### Technologies Used

- AJAX
- JSON responses
- Dynamic DOM manipulation

---

# 🔐 Security Features

## Authentication & Encryption

- Secure password hashing
- Session management
- Login validation

## Input Protection

- SQL Injection prevention
- XSS protection
- Input sanitization

## Request Verification

- Session-based purchase validation
- Unauthorized access blocking

---

# ⚙️ Performance Optimization

SwiftDrop is optimized to handle **1000+ concurrent requests per second**.

## Optimization Techniques

- Database indexing
- Optimized SQL queries
- Minified JS/CSS
- Asynchronous AJAX handling
- Efficient transaction management

---

# 🧪 System Testing

| Test Case | Condition | Result |
|------------|------------|---------|
| Concurrency Test | 100 users buying 5 items | ✅ Exactly 5 items sold |
| Latency Test | High load burst | ✅ < 500ms response |
| Security Test | Unauthorized request | ✅ Request rejected |
| Data Integrity Test | Server crash during sale | ✅ No partial data written |

---

# 🧩 Challenges Faced

## Deadlocks

Excessive row locking caused deadlocks.

### Solution

- Logical query ordering
- Optimized transaction flow

## UI Latency

Stock updates initially felt slow.

### Solution

- Optimized AJAX polling frequency

---

# 🛠️ Technology Stack

## Frontend

- HTML
- CSS
- JavaScript
- AJAX

## Backend

- PHP
- Apache Server

## Database

- MySQL (InnoDB)

## Additional Tools

- Redis
- Queue Workers
- Session Management

---

# 📈 Future Improvements

- WebSocket-based real-time updates
- Cloud auto-scaling deployment
- Microservices architecture
- AI-powered demand prediction
- Distributed caching improvements

---

# 🎉 Conclusion

SwiftDrop successfully demonstrates that a properly designed PHP/MySQL architecture can efficiently handle high-pressure flash sale environments while maintaining:

- High concurrency support
- Data consistency
- Real-time responsiveness
- Zero overselling
- Secure transactions

The project highlights the importance of transaction locking, queue processing, Redis synchronization, and optimized backend engineering in modern e-commerce platforms.

---

# 📄 License

This project is developed for academic and educational purposes.

---

# 🙌 Acknowledgements

Developed by **Predictra**  
Faculty of Computing  
Sabaragamuwa University of Sri Lanka

