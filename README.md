<div align="center">

# 💰 AI-Powered Personal Budget Manager

### Your Intelligent Personal Finance Companion

**Powered by Artificial Intelligence** 🤖

_Take control of your finances with personalized insights, smart budgeting, and AI-driven recommendations_

---

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)](https://php.net)
[![AI Powered](https://img.shields.io/badge/AI-Powered-00D9FF?style=flat&logo=openai)](https://openrouter.ai)

</div>

---

## 🌟 About This Application

**This application** is a revolutionary personal finance management system that combines intelligent budgeting with the power of artificial intelligence. Unlike traditional budget trackers, this AI-powered system understands your unique financial situation your age, income, family responsibilities, and life stage to provide truly personalized financial guidance.

Whether you're a young professional starting your financial journey, a family provider managing multiple responsibilities, or anyone in between, this intelligent system adapts to your needs and helps you make smarter financial decisions.

### 🤖 AI-Powered Intelligence

This application leverages advanced language models from [OpenRouter.ai](https://openrouter.ai), utilizing **free AI models** to deliver enterprise grade financial insights without the enterprise price tag. Every recommendation, insight, and piece of advice is tailored to your specific demographic and financial profile.

---

## ✨ Key Features

### 📊 Smart Budget Management

- **Multi-Category Budgeting**: Create unlimited budgets across various spending categories (Housing, Food, Transportation, Entertainment, Healthcare, etc.)
- **Real-Time Tracking**: Monitor your spending against budgets with live updates and visual indicators
- **Budget Status Alerts**: Automatic warnings when you approach or exceed budget limits (90%, 100% thresholds)
- **Flexible Periods**: Track budgets daily, weekly, monthly, quarterly, or yearly

### 💸 Comprehensive Expense Tracking

- **Quick Entry**: Log expenses in seconds with simple, intuitive interfaces
- **Category Assignment**: Link every expense to its relevant budget and category
- **Historical Analysis**: View detailed spending history with advanced filtering and sorting
- **Transaction Insights**: Analyze spending patterns, average transactions, and frequency

### 📈 Advanced Analytics & Reporting

- **Overview Dashboard**: Get a bird's eye view of all your finances with comprehensive statistics
- **Category Breakdown**: Drill down into specific categories to see detailed spending analysis
- **Visual Progress Tracking**: See exactly how much you've spent, what's remaining, and percentage utilization
- **Trend Analysis**: Identify spending trends over time across different periods
- **Budget vs. Actual Comparison**: Clear visualization of planned vs. actual spending

### 🧠 AI-Powered Financial Intelligence

#### 💬 Interactive AI Assistant

- **Natural Conversations**: Ask your AI assistant anything about your finances in plain language
- **Context-Aware Responses**: The AI understands your complete financial picture when answering questions
- **Personalized Advice**: Get tailored recommendations based on your age, income, family status, and spending patterns

#### 🔍 Personalized Financial Insights

- **Spending Pattern Analysis**: AI analyzes your habits and identifies patterns you might miss
- **Demographic-Based Recommendations**: Age-appropriate and life-stage-relevant financial advice
- **Positive Reinforcement**: Recognition of good financial habits you should maintain
- **Actionable Improvements**: Specific, practical suggestions for better money management
- **Life-Stage Planning**: Future-focused advice considering your current life situation

#### 🎯 Smart Budget Recommendations

- **Income-Based Budgets**: Optimal budget allocations based on your salary and expenses
- **Category-Specific Advice**: Targeted recommendations for individual spending categories
- **Family-Conscious Planning**: Special considerations for family providers and dependents
- **Justified Amounts**: Every recommendation comes with clear reasoning and context

#### 🚨 Anomaly Detection

- **Unusual Spending Alerts**: AI identifies unexpected spikes or irregular patterns in your spending
- **Risk Assessment**: Early warning system for potential budget overruns
- **Date-Specific Analysis**: Pinpoint exactly when and where anomalies occurred
- **Pattern Recognition**: Detect recurring issues before they become problems

#### 💡 Savings Suggestions

- **Goal-Oriented Plans**: Create and work towards specific savings targets
- **Realistic Strategies**: Practical suggestions that fit your lifestyle and income
- **Category-Level Recommendations**: Specific amounts to reduce in each spending category
- **Prioritized Actions**: Focus on high-impact changes that deliver real results

### 🔐 Secure Authentication

- **Email OTP Verification**: Passwordless authentication with one-time passwords sent to your email
- **Social Login Integration**: Quick sign-in with Google and Facebook providers
- **Secure Sessions**: Industry-standard security with Laravel Sanctum API tokens
- **Email Verification**: Ensure account security with verified email addresses

### 👤 Rich User Profiles

- **Demographic Information**: Age, gender, marital status, and more
- **Financial Context**: Salary information for accurate AI recommendations
- **Family Details**: Track family provider status and number of dependents
- **Profile Updates**: Easily update your information as your situation changes

---

## 🛠️ Technology Stack

### Backend

- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum (API tokens)
- **API Architecture**: RESTful API design

### AI & Machine Learning

- **AI Provider**: [OpenRouter.ai](https://openrouter.ai)
- **Model**: `openai/gpt-oss-120b:free` (Free tier)

### Infrastructure

- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **Email**: SMTP (configurable)
- **Queue System**: Laravel Queue workers
- **Caching**: Redis

---

## 🚀 Installation & Setup

### Prerequisites

- Docker & Docker Compose installed
- Git installed
- OpenRouter.ai account (free) - [Sign up here](https://openrouter.ai)

### Step 1: Clone the Repository

```bash
git clone https://github.com/AsmaaGamal30/ai-powered-personal-budget-manager.git
cd budget
```

### Step 2: Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Edit the .env file with your settings
```

### Step 3: Configure OpenRouter.ai

1. Create a free account at [https://openrouter.ai](https://openrouter.ai)
2. Navigate to [API Keys](https://openrouter.ai/keys) and generate a new API key
3. Configure privacy settings at [https://openrouter.ai/settings/privacy](https://openrouter.ai/settings/privacy)
    - For free models, enable "Free model publication"
4. Add your API key to `.env`:

```env
LLM_API_KEY=your_openrouter_api_key_here
LLM_BASE_URL=https://openrouter.ai/api/v1
LLM_MODEL=openai/gpt-oss-120b:free
```

### Step 4: Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=budget
DB_USERNAME=budget_user
DB_PASSWORD=secure_password

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Step 5: Build and Start with Docker

```bash
# Build and start the containers
docker-compose up -d

# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run database migrations
docker-compose exec app php artisan migrate

# Seed the database with categories
docker-compose exec app php artisan db:seed
```

### Step 6: Access the Application

## The API will be available at: `http://localhost:8000`

## 🧪 Testing

The application includes comprehensive test coverage:

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature
```

---

## 🐳 Docker Commands

```bash
# Start the application
docker-compose up -d

# Stop the application
docker-compose down

# View logs
docker-compose logs -f app

# Access the application container
docker-compose exec app bash

# Rebuild containers
docker-compose up -d --build

# Clear Laravel cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
```
