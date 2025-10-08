# BulkHunter Pro - Automated Buyer Discovery System

## Overview
BulkHunter is an intelligent lead generation system that automatically discovers and qualifies high-volume bulk buyers for Sydney Markets vendors.

## System Architecture

### Phase 1: Data Aggregation (Steps 1-25)
- **ABN Registry Integration** (Steps 1-5)
  - Search business registry for food service businesses
  - Filter by Sydney postcodes
  - Extract core business data

- **Google Maps Enrichment** (Steps 6-10)
  - Verify business is active
  - Get contact details and hours
  - Calculate business size indicators

- **Social Media Mining** (Steps 11-15)
  - LinkedIn Sales Navigator monitoring
  - Facebook Groups scanning
  - Instagram business discovery

- **Web Scraping Pipeline** (Steps 16-20)
  - YellowPages, Zomato, TripAdvisor
  - Menulog/UberEats high-volume detection

- **Government Data Import** (Steps 21-25)
  - NSW Food Authority registers
  - Council food licenses

### Phase 2: Lead Qualification (Steps 26-50)
- **Size Estimation Algorithm** (Steps 26-30)
  - Calculate weekly volume requirements
  - Classify: WHALE/BIG/MEDIUM/SMALL
  - Cuisine-specific multipliers

- **Contact Discovery** (Steps 31-35)
  - Email pattern detection
  - LinkedIn contact finding
  - Phone validation

- **Supplier Intelligence** (Steps 36-40)
  - Current supplier identification
  - Satisfaction analysis
  - Switch opportunity detection

- **Financial Qualification** (Steps 41-45)
  - Credit checks
  - Spending potential calculation
  - Payment history analysis

- **Competition Analysis** (Steps 46-50)
  - Current supplier mapping
  - Price sensitivity scoring
  - Quality requirements assessment

### Phase 3: Automated Outreach (Steps 51-75)
- **Lead Prioritization** (Steps 51-55)
  - Final scoring algorithm
  - HOT/WARM/COLD segmentation
  - Vendor assignment

- **Multi-Channel Outreach** (Steps 56-60)
  - Email campaigns
  - SMS outreach
  - LinkedIn automation
  - WhatsApp Business

- **Call Center Integration** (Steps 61-65)
  - Script generation
  - Predictive dialing
  - Call analytics

- **Smart Retargeting** (Steps 66-70)
  - Facebook Custom Audiences
  - Google Ads integration
  - LinkedIn Matched Audiences

- **Referral Automation** (Steps 71-75)
  - Connection identification
  - Incentive management
  - Franchise detection

### Phase 4: Conversion & Tracking (Steps 76-100)
- **Lead Nurturing** (Steps 76-80)
  - Drip campaigns
  - Behavior triggers
  - Win-back sequences

- **Performance Analytics** (Steps 81-85)
  - Funnel tracking
  - ROI analysis
  - CLV prediction

- **AI Optimization** (Steps 86-90)
  - ML model training
  - Contact time optimization
  - Channel selection

- **System Integration** (Steps 91-95)
  - Main app sync
  - CRM webhooks
  - Lead distribution

- **Compliance & Maintenance** (Steps 96-100)
  - SPAM compliance
  - Privacy management
  - Data cleansing

## Database Schema

```sql
-- Buyer leads discovered
CREATE TABLE buyer_leads (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    abn VARCHAR(11) UNIQUE,
    business_name VARCHAR(255) NOT NULL,
    trading_name VARCHAR(255),
    entity_type VARCHAR(100),
    business_type VARCHAR(100),
    category VARCHAR(100),
    subcategory VARCHAR(100),

    -- Location
    address TEXT,
    suburb VARCHAR(100),
    postcode VARCHAR(10),
    state VARCHAR(50),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),

    -- Contact Info
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),

    -- Business Intelligence
    size_classification ENUM('WHALE', 'BIG', 'MEDIUM', 'SMALL'),
    weekly_volume_estimate INT,
    monthly_spend_estimate DECIMAL(10, 2),
    employee_count INT,
    years_in_business INT,

    -- Google Maps Data
    google_place_id VARCHAR(255),
    google_rating DECIMAL(2, 1),
    google_reviews_count INT,
    google_price_level INT,
    opening_hours JSON,
    is_currently_open BOOLEAN,

    -- Supplier Intelligence
    current_supplier VARCHAR(255),
    using_competitor_platform BOOLEAN,
    unhappy_with_supplier BOOLEAN,
    supplier_pain_points TEXT,

    -- Scoring
    size_score INT,
    opportunity_score INT,
    credit_score INT,
    final_score DECIMAL(5, 2),

    -- Status
    status ENUM('NEW', 'ENRICHED', 'QUALIFIED', 'CONTACTED', 'NEGOTIATING', 'CONVERTED', 'LOST'),
    assigned_vendor_id BIGINT,

    -- Metadata
    source VARCHAR(50),
    discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_enriched_at TIMESTAMP,
    last_contacted_at TIMESTAMP,
    converted_at TIMESTAMP,

    INDEX idx_postcode (postcode),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_final_score (final_score DESC),
    INDEX idx_abn (abn)
);

-- Contact persons at businesses
CREATE TABLE lead_contacts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    lead_id BIGINT NOT NULL,
    name VARCHAR(255),
    role VARCHAR(100),
    department VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    linkedin_url VARCHAR(500),
    is_decision_maker BOOLEAN DEFAULT FALSE,
    confidence_score INT,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES buyer_leads(id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_email (email)
);

-- Outreach tracking
CREATE TABLE outreach_campaigns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    lead_id BIGINT NOT NULL,
    contact_id BIGINT,
    channel ENUM('EMAIL', 'SMS', 'CALL', 'LINKEDIN', 'WHATSAPP'),
    campaign_type VARCHAR(50),
    subject VARCHAR(255),
    message TEXT,
    sent_at TIMESTAMP,
    opened_at TIMESTAMP,
    clicked_at TIMESTAMP,
    responded_at TIMESTAMP,
    response TEXT,
    sentiment_score DECIMAL(3, 2),
    converted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES buyer_leads(id),
    FOREIGN KEY (contact_id) REFERENCES lead_contacts(id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_channel (channel)
);

-- Lead notes and activities
CREATE TABLE lead_activities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    lead_id BIGINT NOT NULL,
    activity_type VARCHAR(50),
    description TEXT,
    outcome VARCHAR(100),
    next_action VARCHAR(255),
    next_action_date DATE,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES buyer_leads(id),
    INDEX idx_lead_id (lead_id),
    INDEX idx_created_at (created_at)
);
```

## Implementation Priority

### MVP (Week 1)
1. ABN Lookup Integration
2. Google Maps Enrichment
3. Basic Lead Scoring
4. Simple Email Outreach
5. Performance Dashboard

### Phase 2 (Week 2-3)
1. Web Scraping Pipeline
2. Advanced Scoring Algorithm
3. Multi-channel Outreach
4. CRM Integration
5. Vendor Assignment System

### Phase 3 (Week 4+)
1. AI/ML Optimization
2. Predictive Analytics
3. Automated Nurturing
4. Advanced Retargeting
5. Full Automation

## Success Metrics

### Target KPIs
- Leads Generated: 5,000/month
- Qualified Leads: 500/month (10kg+ weekly)
- Contact Rate: 10%
- Conversion Rate: 2%
- New Customers: 100/month
- Revenue per Customer: $2,000/month
- Total New Revenue: $200,000/month

### ROI Projection
- Development Cost: $10,000
- Monthly Operating Cost: $2,000
- Monthly Revenue: $200,000
- ROI: 9,900% annually

## Competitive Advantage
This system provides an unfair advantage by:
1. Proactively identifying buyers before competitors
2. Qualifying based on actual business intelligence
3. Automating the entire discovery-to-conversion pipeline
4. Creating a data moat competitors cannot replicate
5. Enabling vendors to focus on fulfillment vs. sales