document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebarMenu");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const content = document.querySelector(".main-content");
  const overlay = document.createElement("div");
  overlay.className = "sidebar-overlay";
  document.body.appendChild(overlay);

  function openSidebar() {
    sidebar.classList.add("show");
    document.body.classList.add("sidebar-open");
    overlay.style.display = "block";
    sidebarToggle.querySelector("i").classList.replace("fa-bars", "fa-times");
  }

  function closeSidebar() {
    sidebar.classList.remove("show");
    document.body.classList.remove("sidebar-open");
    overlay.style.display = "none";
    sidebarToggle.querySelector("i").classList.replace("fa-times", "fa-bars");
  }

  sidebarToggle.addEventListener("click", function () {
    if (sidebar.classList.contains("show")) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  overlay.addEventListener("click", closeSidebar);

  // ปิด sidebar เมื่อคลิกที่ลิงก์ใน sidebar (สำหรับหน้าจอมือถือ)
  const sidebarLinks = sidebar.querySelectorAll("a");
  sidebarLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (window.innerWidth < 768) {
        closeSidebar();
      }
    });
  });

  // ปิด sidebar เมื่อหน้าจอมีขนาดใหญ่ขึ้น
  window.addEventListener("resize", function () {
    if (window.innerWidth >= 768) {
      closeSidebar();
    }
  });

  // จัดการการค้นหา
  var searchInput = document.querySelector(".search-input");
  if (searchInput) {
    searchInput.addEventListener("keyup", function () {
      var value = this.value.toLowerCase();
      var rows = document.querySelectorAll("table tbody tr");
      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(value) > -1 ? "" : "none";
      });
    });
  }
  // จัดการ Modal แก้ไขและลบ
  var editButtons = document.querySelectorAll(".edit-btn");
  var deleteButtons = document.querySelectorAll(".delete-btn");
  var editModalElement = document.getElementById("editModal");
  var deleteModalElement = document.getElementById("deleteModal");
  if (editModalElement && deleteModalElement) {
    var editModal = new bootstrap.Modal(editModalElement);
    var deleteModal = new bootstrap.Modal(deleteModalElement);
    editButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        var treeId = this.getAttribute("data-tree-id");
        document.getElementById("editTreeId").value = treeId;
        document.getElementById("sellTreeId").value = treeId;
        editModal.show();
      });
    });
    deleteButtons.forEach(function (button) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        var treeId = this.getAttribute("data-tree-id");
        document.getElementById("deleteTreeId").value = treeId;
        deleteModal.show();
      });
    });
    // ปิด Modal เมื่อคลิกปุ่มยกเลิก
    document
      .querySelectorAll('[data-bs-dismiss="modal"]')
      .forEach(function (button) {
        button.addEventListener("click", function () {
          editModal.hide();
          deleteModal.hide();
        });
      });
  }
  // จัดการ Tab ในหน้าแก้ไข
  var editModalElement = document.getElementById("editModal");
  if (editModalElement) {
    editModalElement.addEventListener("show.bs.modal", function (event) {
      var button = event.relatedTarget;
      var treeId = button.getAttribute("data-tree-id");
      var modal = this;
      modal.querySelector("#editTreeId").value = treeId;
      modal.querySelector("#sellTreeId").value = treeId;
    });
    var tabElements = editModalElement.querySelectorAll(
      'button[data-bs-toggle="tab"]'
    );
    tabElements.forEach(function (tab) {
      tab.addEventListener("shown.bs.tab", function (event) {
        var activeTab = event.target;
        var targetId = activeTab.getAttribute("data-bs-target");
        var targetPane = editModalElement.querySelector(targetId);
        if (targetPane) {
          targetPane.classList.add("show", "active");
        }
      });
    });
  }
  // จัดการการส่งฟอร์ม
  var forms = document.querySelectorAll("form");
  forms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      // ไม่ต้องใช้ e.preventDefault() เพื่อให้ฟอร์มส่งข้อมูลไปยัง PHP ได้
      console.log("Form submitted:", this);
      // ปิด Modal หลังจากส่งฟอร์ม
      var modal = bootstrap.Modal.getInstance(this.closest(".modal"));
      if (modal) {
        modal.hide();
      }
    });
  });
});
document.addEventListener("DOMContentLoaded", function () {
  const treeDetails = document.querySelectorAll(".tree-detail");
  treeDetails.forEach(function (td) {
    td.addEventListener("click", function () {
      const treeId = this.getAttribute("data-tree-id");
      window.location.href = "tree-detail.php?id=" + treeId;
    });
  });
});
