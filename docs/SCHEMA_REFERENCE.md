# Database Schema Reference

**Last Updated:** January 22, 2026  
**Status:** ✅ Verified against schema

---

## 🔍 CRITICAL FIELD NAMES (Common Mistakes)

| Table            | ❌ WRONG                | ✅ CORRECT                       |
| ---------------- | ----------------------- | -------------------------------- |
| expense          | ExpStatus               | ApprovalStatus                   |
| expense          | ExpPurpose              | ExpDescription                   |
| expense          | ProofFile               | ReceiptImageURL                  |
| expense_category | ExpCategoryName         | CategoryName                     |
| contribution     | ContributionDescription | Notes                            |
| payment_method   | MethodID                | PaymentMethodID                  |
| payment_method   | MethodName              | PaymentMethodName                |
| branch           | BranchPhone             | BranchPhoneNumber                |
| branch           | BranchEmail             | BranchEmailAddress               |
| churchmember     | MbrMembershipStatus     | MbrMembershipStatusID (use JOIN) |

---

## 📋 TABLES WITHOUT SOFT DELETE

These tables do NOT have `Deleted` columns:

- `family` - Use hard delete
- `fiscal_year`
- `branch`
- `church_role`
- `permission`
- `payment_method`
- `contribution_type`

---

## 💡 QUICK EXAMPLES

### Expense Query

```php
$orm->runQuery(
    "SELECT e.ExpID, e.ExpTitle, e.ExpDescription, e.ExpAmount,
            e.ApprovalStatus, e.ReceiptImageURL
     FROM expense e
     WHERE e.ApprovalStatus = 'Approved'"
);
```

### Member Query with Status

```php
$orm->runQuery(
    "SELECT m.MbrID, m.MbrFirstName, mst.StatusName
     FROM churchmember m
     JOIN membership_status mst ON m.MbrMembershipStatusID = mst.StatusID
     WHERE mst.StatusName = 'Active'"
);
```

### Contribution Query

```php
$orm->runQuery(
    "SELECT c.ContributionID, c.Notes, pm.PaymentMethodName
     FROM contribution c
     JOIN payment_method pm ON c.PaymentMethodID = pm.PaymentMethodID"
);
```

---

**For full schema details, refer to:** `alive_chms.sql`
