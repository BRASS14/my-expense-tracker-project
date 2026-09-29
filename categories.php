<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Add Category
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_category'])){
    $name = trim($conn->real_escape_string($_POST['name']));
    $type = $conn->real_escape_string($_POST['type']);
    if($name && $type){
        $conn->query("INSERT INTO categories (name,type) VALUES ('$name','$type')");
    }
    header("Location: categories.php");
    exit;
}

// Edit Category
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_category'])){
    $id = intval($_POST['id']);
    $name = trim($conn->real_escape_string($_POST['name']));
    $type = $conn->real_escape_string($_POST['type']);
    if($id && $name && $type){
        $conn->query("UPDATE categories SET name='$name', type='$type' WHERE id=$id");
    }
    header("Location: categories.php");
    exit;
}

// Delete Category
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_category'])){
    $id = intval($_POST['id']);
    if($id){
        $conn->query("DELETE FROM categories WHERE id=$id");
    }
    header("Location: categories.php");
    exit;
}

// Search filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = "";
if($search){
    $where = "WHERE name LIKE '%$search%' OR type LIKE '%$search%'";
}

// Pagination setup
$limit = 10; // categories per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total categories
$count_query = $conn->query("SELECT COUNT(*) AS total FROM categories $where");
$total_categories = $count_query->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $limit);

// Fetch categories with pagination
$categories = $conn->query("SELECT * FROM categories $where ORDER BY type,name ASC LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user_dashboard.php">Expense Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="user.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="report.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Manage Categories</h1>

    <!-- Add Category -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Add New Category</h3>
        <form method="POST" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="Category name" required>
            </div>
            <div class="col-md-4">
                <select name="type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>

    <!-- Search Bar -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Search Categories</h3>
        <form method="GET" class="row g-3">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" placeholder="Search by name or type" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary">Search</button>
                <a href="categories.php" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <div class="card p-4 shadow-sm">
        <h3>Existing Categories</h3>
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($categories->num_rows > 0){ ?>
                    <?php while($row=$categories->fetch_assoc()){ ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo ($row['type']=='income')?'success':'danger'; ?>">
                                <?php echo ucfirst($row['type']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- Edit Form -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" class="form-control d-inline w-auto" required>
                                <select name="type" class="form-select d-inline w-auto" required>
                                    <option value="income" <?php if($row['type']=='income') echo 'selected'; ?>>Income</option>
                                    <option value="expense" <?php if($row['type']=='expense') echo 'selected'; ?>>Expense</option>
                                </select>
                                <button type="submit" name="edit_category" class="btn btn-warning btn-sm">Edit</button>
                            </form>

                            <!-- Delete Form -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="4" class="text-center">No categories found.</td></tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
          <ul class="pagination justify-content-center">
            <?php if($page > 1){ ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
              </li>
            <?php } ?>
            
            <?php for($i=1;$i<=$total_pages;$i++){ ?>
              <li class="page-item <?php echo ($i==$page)?'active':''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
              </li>
            <?php } ?>
            
            <?php if($page < $total_pages){ ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
              </li>
            <?php } ?>
          </ul>
        </nav>
    </div>
</div>

</body>
</html>
