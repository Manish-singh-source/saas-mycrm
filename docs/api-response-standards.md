Yes — you mean a **standardized API response structure categorized by CRUD operations**.

A clean Laravel REST API standard would be:

### 1. CREATE — POST

**Success — `201 Created`**

```json
{
    "success": true,
    "message": "User created successfully.",
    "data": {}
}
```

**Error — `422 Unprocessable Content`**

```json
{
    "success": false,
    "message": "Failed to create user.",
    "errors": {}
}
```

---

### 2. READ — GET (Single)

**Success — `200 OK`**

```json
{
    "success": true,
    "message": "User fetched successfully.",
    "data": {}
}
```

**Error — `404 Not Found`**

```json
{
    "success": false,
    "message": "User not found.",
    "data": null
}
```

---

### 3. READ — GET (List)

**Success — `200 OK`**

```json
{
    "success": true,
    "message": "Users fetched successfully.",
    "data": [],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 100,
        "last_page": 10
    }
}
```

**Error — `500 Internal Server Error`**

```json
{
    "success": false,
    "message": "Failed to fetch users.",
    "data": null
}
```

---

### 4. UPDATE — PUT/PATCH

**Success — `200 OK`**

```json
{
    "success": true,
    "message": "User updated successfully.",
    "data": {}
}
```

**Error — `422 Unprocessable Content`**

```json
{
    "success": false,
    "message": "Failed to update user.",
    "errors": {}
}
```

---

### 5. DELETE — DELETE

**Success — `200 OK`**

```json
{
    "success": true,
    "message": "User deleted successfully.",
    "data": null
}
```

Alternatively, use **`204 No Content`** and return no JSON body.

**Error — `404 Not Found`**

```json
{
    "success": false,
    "message": "User not found.",
    "data": null
}
```

---

### 6. VALIDATION ERROR

**`422 Unprocessable Content`**

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "email": [
            "The email must be a valid email address."
        ]
    }
}
```

---

### 7. SERVER / UNEXPECTED ERROR

**`500 Internal Server Error`**

```json
{
    "success": false,
    "message": "Something went wrong. Please try again later.",
    "data": null
}
```

### Recommended CRUD mapping

| Operation        | Success       | Common Error        |
| ---------------- | ------------- | ------------------- |
| **Create**       | `201`         | `422`, `409`, `500` |
| **Read Single**  | `200`         | `404`, `500`        |
| **Read List**    | `200`         | `500`               |
| **Update**       | `200`         | `404`, `422`, `500` |
| **Delete**       | `200` / `204` | `404`, `500`        |
| **Validation**   | —             | `422`               |
| **Server Error** | —             | `500`               |

For a **Laravel boilerplate**, I'd make these response formats reusable through a `BaseApiController` or `ApiResponse` helper so every module returns the exact same structure.
