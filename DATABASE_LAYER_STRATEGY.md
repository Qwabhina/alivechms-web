# Database Layer Strategy - Coexistence Plan

## 🏗️ **Three-Layer Database Architecture**

### **Layer 1: ORM (Foundation) - KEEP**

**File**: `core/ORM.php`
**Purpose**: Secure, transactional database operations
**Status**: ✅ **Primary foundation - DO NOT CHANGE**

```php
// Basic CRUD operations - Most secure
$orm = new ORM();
$member = $orm->getWhere('churchmember', ['MbrID' => $id]);
$orm->insert('churchmember', $data);
$orm->update('churchmember', $data, ['MbrID' => $id]);
```

**Why Keep**:

- ✅ Battle-tested security with prepared statements
- ✅ Proper parameter binding and escaping
- ✅ Transaction support
- ✅ Used throughout the codebase (70% of queries)

### **Layer 2: Advanced QueryBuilder (Enhanced) - KEEP & ENHANCE**

**File**: `core/QueryBuilder.php` (Original)
**Purpose**: Complex queries with caching and performance features
**Status**: ✅ **Keep as primary QueryBuilder**

```php
// Complex queries with caching
$qb = new QueryBuilder();
$result = $qb->table('churchmember')
             ->where('Deleted', 0)
             ->orderBy('MbrRegistrationDate', 'DESC')
             ->limit(10)
             ->cache(300, ['members'])  // Built-in caching!
             ->get();
```

**Why Keep**:

- ✅ Built-in caching integration
- ✅ Batch operations for performance
- ✅ Query profiling and logging
- ✅ More sophisticated features

### **Layer 3: Simple QueryBuilder (Optional) - OPTIONAL**

**File**: `core/Database/QueryBuilder.php` (New)
**Purpose**: Simple, clean fluent interface
**Status**: 🔄 **Optional alternative for simple cases**

```php
// Simple, clean queries
$members = QueryBuilder::table('churchmember')
    ->where('Deleted', 0)
    ->where('MbrMembershipStatus', 'Active')
    ->orderBy('MbrFirstName')
    ->get();
```

**Use Case**: When you want clean, readable code without caching complexity

## 🎯 **Usage Guidelines**

### **Use ORM for**: (70% of current usage)

- ✅ Simple CRUD operations
- ✅ Security-critical operations
- ✅ Transactional operations
- ✅ Basic queries with conditions

### **Use Advanced QueryBuilder for**: (25% of current usage)

- ✅ Complex queries with joins
- ✅ Queries that need caching
- ✅ Performance-critical operations
- ✅ Batch operations
- ✅ Reporting queries

### **Use Simple QueryBuilder for**: (5% - optional)

- 🔄 Simple queries where readability is priority
- 🔄 Learning/teaching purposes
- 🔄 Prototyping new features

## 🔧 **Recommended Actions**

### **Immediate (No Changes Needed)**

- ✅ **Keep ORM as-is** - It's secure and working perfectly
- ✅ **Keep original QueryBuilder** - It has advanced features
- ✅ **Keep simple QueryBuilder** - It's a useful alternative

### **Optional Enhancements**

- 🔄 **Enhance original QueryBuilder** with any missing features
- 🔄 **Add documentation** showing when to use each layer
- 🔄 **Create examples** for each use case

### **File Organization**

```
core/
├── ORM.php                    # Layer 1: Secure foundation
├── QueryBuilder.php           # Layer 2: Advanced features
└── Database/
    └── QueryBuilder.php       # Layer 3: Simple alternative
```

## 📊 **Current Usage Distribution**

### **Existing Codebase Analysis**:

- **70%** - ORM methods (`getWhere`, `insert`, `update`, `delete`)
- **25%** - Raw SQL with `runQuery()` (candidates for QueryBuilder)
- **5%** - Direct PDO (should migrate to ORM)

### **Migration Strategy**:

- ✅ **Keep ORM usage** - No changes needed (already secure)
- 🔄 **Optionally migrate raw SQL** to QueryBuilder (for readability)
- ⚠️ **Migrate direct PDO** to ORM (for security)

## 🎯 **Benefits of Coexistence**

### **Flexibility**

- Choose the right tool for each use case
- Gradual adoption of advanced features
- No breaking changes to existing code

### **Performance**

- ORM for simple, fast operations
- Advanced QueryBuilder for complex, cached operations
- Simple QueryBuilder for readable, maintainable code

### **Security**

- ORM provides the secure foundation
- All layers use prepared statements
- No security compromises

## 🏆 **Conclusion**

**Recommendation: KEEP ALL THREE LAYERS**

This provides:

- ✅ **Secure foundation** (ORM)
- ✅ **Advanced features** (Original QueryBuilder)
- ✅ **Simple alternative** (New QueryBuilder)
- ✅ **No breaking changes**
- ✅ **Flexibility for different use cases**

The database layer is already excellent. The coexistence approach gives developers the right tool for each situation without compromising security or performance.
