# Libraro UI & Color Design Guidelines

## Primary Color Rule
- **Primary Color**: ALWAYS use **Navy Blue (`#18225f`)** as the primary color across all UI elements, buttons, headers, active states, and icons.
- **NEVER use standard blue (`#007bff`, `#0d6efd`, plain blue)** for primary elements.
- **Secondary / Accent Colors**:
  - Teal Accent: `#34939F`
  - Success Green: `#22c55e` / `#16a34a`
  - Danger Red: `#dc3545` / `#ef4444`
  - Dark Navy Text: `#18225f` / `#1e293b`

---

## Standard UI Layout Format for List Views & CRUD
All list views and CRUD modules in Libraro WebGuard MUST follow this identical layout structure:

### 1. Page Header Block
```html
<div class="heading-list py-4 d-flex justify-content-between align-items-center">
    <h4 class="mb-0 fw-bold" style="color: #18225f; font-family: 'Outfit', sans-serif;">[Module Name] List</h4>
    <a href="[Create Route]" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> [Add Button Label]</a>
</div>
```

### 2. Primary Button Style
- Background: `#18225f` !important
- Color: `#ffffff` !important
- Border-radius: Pill / 8px

### 3. Standard Table Block & DataTables
```html
<div class="table-responsive mb-4">
    <table class="table text-center" id="datatable">
        <thead>
            <tr>
                <th>S.No.</th>
                <th>[Column 1]</th>
                <th>[Column 2]</th>
                <th style="width:20%">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>...</td>
                <td>...</td>
                <td>
                    <ul class="actionalbls">
                        <li><a href="..." data-bs-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a></li>
                        <li><a href="..." data-bs-toggle="tooltip" title="Delete" onclick="return confirm('...');"><i class="fas fa-trash"></i></a></li>
                    </ul>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### 4. Action Icon List (`.actionalbls`)
- Edit icon: `<i class="fas fa-edit"></i>`
- Delete icon: `<i class="fas fa-trash"></i>`
- Icon container: Square rounded box `width: 32px; height: 32px; background: #f1f5f9; color: #18225f;`
