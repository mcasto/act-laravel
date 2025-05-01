After migrating and seeding your database, you’ll want to perform various tests to ensure data integrity, consistency, and correctness. Here’s a breakdown of different types of tests you might run:

---

### **5. Performance & Business Logic Tests**
#### ✅ Test ticket sales logic:
- Ensure `sold_out_target` prevents overselling.
```php
$performance = Performance::first();
$this->assertLessThanOrEqual($performance->sold_out_target, $performance->tickets()->count());
```

#### ✅ Validate donation levels:
- Ensure `amount_min` ≤ `amount_max`.
```php
$level = DonationLevel::first();
$this->assertLessThanOrEqual($level->amount_min, $level->amount_max);
```

#### ✅ Check email uniqueness in Mailchimp:
```php
$emails = MailchimpMember::pluck('email')->toArray();
$this->assertCount(count(array_unique($emails)), $emails);
```

---

### **6. Data Cleanup & Consistency Tests**
#### ✅ Ensure orphaned records don’t exist:
```php
$this->assertDatabaseMissing('tickets', ['performance_id' => null]);
```

#### ✅ Ensure soft deletes work (if applicable):
```php
$patron = Patron::first();
$patron->delete();
$this->assertSoftDeleted('patrons', ['id' => $patron->id]);
```

#### ✅ Ensure date fields store valid timestamps:
```php
$show = Show::first();
$this->assertTrue($show->ticket_sales_start instanceof Carbon);
```

---

### **7. Edge Case & Security Tests**
#### ✅ Test inserting malicious data (SQL injection protection):
```php
$this->expectException(QueryException::class);
User::create(['name' => "'; DROP TABLE users; --", 'email' => 'test@example.com', 'password' => 'secret']);
```

#### ✅ Ensure relationships don’t allow incorrect associations:
```php
$invalidTicket = Ticket::create([
    'performance_id' => 9999, // Non-existent ID
    'patron_id' => 1
]);
$this->assertNull($invalidTicket);
```

---

## **Conclusion**
This list provides a strong foundation for testing your migrated and seeded data. A mix of **database integrity checks**, **relationship validation**, **business logic tests**, and **security tests** ensures your data is accurate, well-structured, and free of common issues. 🚀
