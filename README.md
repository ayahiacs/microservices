# Microservices Platform

A modern microservices architecture built with Laravel 12, featuring real-time communication, event-driven design, and containerized deployment.

## Section 1: Overview

### Brief Description of the System

This is a distributed microservices platform consisting of two main services:

- **HR Service**: Manages employee data and publishes domain events
- **Hub Service**: Aggregates data from multiple services and provides real-time updates to clients

The system follows an event-driven architecture pattern where services communicate asynchronously through message queues, ensuring loose coupling and scalability.

### Technology Stack Used

#### Backend Framework
- **Laravel 12**: Modern PHP framework with streamlined architecture
- **PHP 8.5**: Latest PHP version with enhanced performance and features

#### Communication & Messaging
- **RabbitMQ 4.2.4**: Message broker for asynchronous service communication
- **Soketi WebSocket Server**: Real-time bidirectional communication
- **Pusher**: WebSocket abstraction for Laravel broadcasting

#### Database & Caching
- **PostgreSQL 18**: Primary relational database
- **Redis**: In-memory caching and session storage

#### Development & Testing
- **Pest 4**: Modern PHP testing framework
- **Docker & Docker Compose**: Containerization and orchestration
- **Nginx**: Web server (via webdevops/php-nginx image)

#### Monitoring & Health
- **Spatie Laravel Health**: Application health monitoring
- **Laravel Pail**: Real-time log tailing

### Design Decisions and Trade-offs

#### Event-Driven Architecture
**Decision**: Adopted event-driven communication between services using RabbitMQ
**Rationale**: 
- Enables loose coupling between services
- Provides resilience through asynchronous communication
- Supports scalability and independent service deployment

**Trade-offs**: 
- Increased complexity in debugging and tracing
- Eventual consistency vs. immediate consistency
- Additional infrastructure overhead

#### Cache-Aside Pattern
**Decision**: Implemented cache-aside pattern with Redis in Hub Service
**Rationale**:
- Reduces database load for frequently accessed data
- Improves response times for aggregated data
- Provides flexibility in cache invalidation strategies

**Trade-offs**:
- Cache invalidation complexity
- Potential for stale data
- Additional memory requirements

#### WebSocket Broadcasting
**Decision**: Used Soketi with Pusher-compatible API for real-time updates
**Rationale**:
- Native Laravel broadcasting support
- Scalable WebSocket solution
- Simple client integration

**Trade-offs**:
- Single point of failure for WebSocket connections
- Network complexity for real-time features
- Additional monitoring requirements

## Section 2: Architecture

### System Architecture Diagram

```
┌─────────────────┐    ┌─────────────────┐
│   HR Service    │    │   Hub Service   │
│   (Port 8000)   │    │   (Port 8001)   │
│                 │    │                 │
│  ┌───────────┐  │    │  ┌───────────┐  │
│  │ Laravel   │  │    │  │ Laravel   │  │
│  │ API       │  │    │  │ API       │  │
│  └───────────┘  │    │  └───────────┘  │
│        │        │    │        │        │
│        │        │    │  ┌───────────┐  │
│        │        │    │  │ WebSocket │  │
│        │        │    │  │ Server    │  │
│        │        │    │  └───────────┘  │
└─────────┬────────┘    └─────────┬────────┘
          │                      │
          └──────────┬───────────┘
                     │
          ┌─────────────────┐
          │    RabbitMQ     │
          │   (Port 5672)   │
          │                 │
          │  Event Queue    │
          └─────────────────┘
                     │
          ┌─────────────────┐
          │   PostgreSQL    │
          │                 │
          │  HR Database    │
          │  Hub Database   │
          └─────────────────┘
                     │
          ┌─────────────────┐
          │     Redis       │
          │                 │
          │   Cache Store   │
          └─────────────────┘

External Clients:
┌─────────────────┐    ┌─────────────────┐
│   Web Client    │    │  Mobile Client  │
│                 │    │                 │
│  HTTP API       │    │  WebSocket      │
│  WebSocket      │    │  HTTP API       │
└─────────────────┘    └─────────────────┘
```

### Data Flow Explanation

#### 1. Employee Data Management Flow

```
Client Request → HR Service → PostgreSQL → Event Published → RabbitMQ → Hub Service → Cache Update → WebSocket Broadcast
```

**Step-by-step process:**

1. **Client Request**: HTTP request to HR Service for employee operations (CRUD)
2. **Data Persistence**: HR Service validates and stores employee data in PostgreSQL
3. **Event Publishing**: HR Service publishes domain events (EmployeeCreated, EmployeeUpdated, EmployeeDeleted) to RabbitMQ
4. **Message Consumption**: Hub Service consumes events from RabbitMQ queue
5. **Cache Management**: Hub Service updates Redis cache with latest employee data
6. **Real-time Updates**: Hub Service broadcasts changes to connected WebSocket clients

#### 2. Real-time Data Aggregation Flow

```
Client WebSocket → Soketi → Hub Service → Redis Cache → Aggregated Response → WebSocket Broadcast
```

**Key characteristics:**

- **Low Latency**: WebSocket connections provide real-time communication
- **Cache-first**: Hub Service prioritizes cached data for faster responses
- **Event-driven**: All data changes trigger automatic cache invalidation and updates
- **Scalable**: Multiple Hub Service instances can consume from the same RabbitMQ queue

#### 3. Service Communication Patterns

**Publish-Subscribe Pattern:**
- HR Service publishes events without knowing about consumers
- Hub Service subscribes to relevant events
- Decoupled communication enables independent scaling

**Request-Response Pattern:**
- Direct HTTP API calls for synchronous operations
- Used for queries and immediate response requirements
- Fallback when real-time updates are not critical

#### 4. Data Consistency Strategy

**Eventual Consistency:**
- Services maintain their own data stores
- Synchronization occurs through events
- Short periods of inconsistency are acceptable for this use case

**Cache Invalidation:**
- Proactive cache updates on data changes
- TTL-based expiration for safety
- Manual cache clearing when needed

#### 5. Error Handling and Resilience

**Message Queue Resilience:**
- RabbitMQ ensures message persistence
- Failed message retries with exponential backoff
- Dead letter queues for problematic messages

**Service Isolation:**
- Each service has its own database
- Service failures don't cascade to other services
- Health monitoring with automatic recovery

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git

### Quick Start

```bash
# Clone the repository
git clone <repository-url>
cd microservices

# Start all services
docker compose up -d

# Access services
# HR Service: http://localhost:8000
# Hub Service: http://localhost:8001
# RabbitMQ Management: http://localhost:15672
```

### Environment Configuration

Copy `.env.example` to `.env` and configure:

```bash
# Database credentials
POSTGRES_PASSWORD=your_secure_password
HR_DATABASE=hr
HUB_DATABASE=hub

# RabbitMQ settings
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

# Redis configuration
REDIS_HOST=redis
REDIS_PORT=6379

# WebSocket (Soketi) settings
SOKETI_APP_ID=p_a_i_challenge
SOKETI_APP_KEY=p_a_k_challenge
SOKETI_APP_SECRET=p_a_s_challenge
```

## Development

### Running Tests

```bash
# HR Service tests
docker compose exec -i --user application hr ./vendor/bin/pest

# Hub Service tests
docker compose exec -i --user application hub ./vendor/bin/pest
```

### Testing websokets
```bash
# Run migrations and seeder to seed 10 employees for testing using the following command
docker exec -i --user application hr php artisan migrate:fresh --seed
```
Then, you can test the websockets from this page.
http://localhost:8001/checklists.html?country=GERMANY

### Monitoring

- **Health Checks**: `/health` endpoint on each service
- **RabbitMQ Management**: http://localhost:15672

## Deployment

The system is designed for containerized deployment using Docker Compose. For production:

1. Use external managed services (PostgreSQL, Redis, RabbitMQ)
2. Implement proper secrets management
3. Set up monitoring and alerting
4. Configure load balancers for high availability