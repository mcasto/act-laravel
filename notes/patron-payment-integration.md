### **Interactions Between Patrons, Payment Methods, and Patron Payment IDs**  

#### **1. Patrons and Payment Methods**  
- Patrons **can pay using different methods** (Fixr, PayPal, bank transfer, etc.).
- Since payments can be manual (entered by admins), **not all patrons will have an external ID** linked to a specific payment provider.
- Payment methods are **predefined and stored** in a `payment_methods` table.
- A patron **can use multiple payment methods over time**, so their external payment IDs should be stored separately.

#### **2. Patron Payment IDs**  
- Since a patron can use different payment processors over time, **storing external IDs in the `patrons` table** would be limiting.
- Instead, we create a `patron_payment_ids` table where:
  - Each row stores a **patron_id**, a **payment_method_id**, and the **external ID** used with that payment method.
  - This allows a patron to have multiple external IDs (one for Fixr, another for PayPal, etc.).
  - If a patron changes payment methods, their old IDs remain in the system.

### **Relationships Summary**
- **Patrons → Patron Payment IDs (1-to-Many):** A patron can have multiple external IDs (Fixr, PayPal, etc.).
- **Payment Methods → Patron Payment IDs (1-to-Many):** Each external ID corresponds to a specific payment method.
- **Patrons → Payment Methods (via Patron Payment IDs, Many-to-Many indirectly):** Patrons can use multiple payment methods, but only one per transaction.

---

### **Laravel Migrations**  

#### **1. `payment_methods` Migration**
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Fixr", "PayPal", "Bank Transfer"
            $table->string('slug')->unique(); // e.g., "fixr", "paypal", "transfer"
            $table->boolean('user_option')->default(true); // Whether users can select this method
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payment_methods');
    }
};
```

---

#### **2. `patrons` Migration**
```php
return new class extends Migration {
    public function up() {
        Schema::create('patrons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('patrons');
    }
};
```

---

#### **3. `patron_payment_ids` Migration**
```php
return new class extends Migration {
    public function up() {
        Schema::create('patron_payment_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patron_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('external_payment_id')->unique(); // Fixr UUID, PayPal ID, etc.
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('patron_payment_ids');
    }
};
```

---

### **Laravel Models**  

#### **1. `PaymentMethod` Model**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model {
    use HasFactory;

    protected $fillable = ['name', 'slug', 'user_option'];

    public function patronPaymentIds() {
        return $this->hasMany(PatronPaymentId::class);
    }
}
```

---

#### **2. `Patron` Model**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patron extends Model {
    use HasFactory;

    protected $fillable = ['name', 'email'];

    public function paymentIds() {
        return $this->hasMany(PatronPaymentId::class);
    }

    public function paymentMethods() {
        return $this->belongsToMany(PaymentMethod::class, 'patron_payment_ids')
                    ->withPivot('external_payment_id')
                    ->withTimestamps();
    }
}
```

---

#### **3. `PatronPaymentId` Model**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatronPaymentId extends Model {
    use HasFactory;

    protected $fillable = ['patron_id', 'payment_method_id', 'external_payment_id'];

    public function patron() {
        return $this->belongsTo(Patron::class);
    }

    public function paymentMethod() {
        return $this->belongsTo(PaymentMethod::class);
    }
}
```

---

### **How This Works in Laravel**
- **Adding a new payment method:**  
  ```php
  PaymentMethod::create(['name' => 'PayPal', 'slug' => 'paypal', 'user_option' => true]);
  ```

- **Linking a patron to an external payment ID:**  
  ```php
  $patron = Patron::find(1);
  $fixrMethod = PaymentMethod::where('slug', 'fixr')->first();

  $patron->paymentIds()->create([
      'payment_method_id' => $fixrMethod->id,
      'external_payment_id' => 'fixr-uuid-12345',
  ]);
  ```

- **Getting a patron’s payment IDs:**  
  ```php
  $patron = Patron::find(1);
  foreach ($patron->paymentIds as $paymentId) {
      echo "Payment Method: {$paymentId->paymentMethod->name}, ID: {$paymentId->external_payment_id}";
  }
  ```

- **Finding a patron by external payment ID:**  
  ```php
  $paymentId = PatronPaymentId::where('external_payment_id', 'fixr-uuid-12345')->first();
  $patron = $paymentId ? $paymentId->patron : null;
  ```

---

### **Advantages of This Approach**
✅ **Scalable** – Easily add new payment methods without changing the database structure.  
✅ **Flexible** – A patron can switch between Fixr, PayPal, or any other method seamlessly.  
✅ **Decoupled** – Payment methods are stored separately, making reporting and management easier.  
✅ **Preserves History** – Old external payment IDs remain even if a patron uses a different method later.  

---

Does this structure work for your needs? 🚀
