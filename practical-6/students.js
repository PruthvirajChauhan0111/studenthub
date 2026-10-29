
document.addEventListener("DOMContentLoaded", async function () {
    const searchInput = document.getElementById("studentSearch");
    const courseFilter = document.getElementById("courseFilter");
    const resetButton = document.getElementById("resetFilters");
    const tableBody = document.getElementById("studentTableBody");
    const resultCount = document.getElementById("resultCount");
    const pageInfo = document.getElementById("pageInfo");
    const previousButton = document.getElementById("previousPage");
    const nextButton = document.getElementById("nextPage");
    const message = document.getElementById("directoryMessage");

    if (!searchInput || !courseFilter || !tableBody) return;

    const pageSize = 5;
    let students = [];
    let currentPage = 1;

    function createCell(value) {
        const cell = document.createElement("td");
        cell.textContent = String(value);
        return cell;
    }

    function getFilteredStudents() {
        const query = searchInput.value.trim().toLowerCase();
        const selectedCourse = courseFilter.value;

        return students.filter(function (student) {
            const matchesSearch = [
                student.id,
                student.name,
                student.email
            ].some(function (value) {
                return String(value).toLowerCase().includes(query);
            });

            const matchesCourse =
                selectedCourse === "all" ||
                student.course === selectedCourse;

            return matchesSearch && matchesCourse;
        });
    }

    function renderStudents() {
        const filteredStudents = getFilteredStudents();
        const totalStudents = filteredStudents.length;
        const totalPages = Math.max(1, Math.ceil(totalStudents / pageSize));

        currentPage = Math.min(currentPage, totalPages);

        const startIndex = (currentPage - 1) * pageSize;
        const visibleStudents = filteredStudents.slice(
            startIndex,
            startIndex + pageSize
        );

        tableBody.replaceChildren();

        if (visibleStudents.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");

            cell.colSpan = 5;
            cell.textContent = "No students match your search.";
            row.appendChild(cell);
            tableBody.appendChild(row);
        } else {
            visibleStudents.forEach(function (student) {
                const row = document.createElement("tr");

                row.append(
                    createCell(student.id),
                    createCell(student.name),
                    createCell(student.email),
                    createCell(student.course),
                    createCell(student.semester)
                );

                tableBody.appendChild(row);
            });
        }

        resultCount.textContent =
            "Showing " + (totalStudents ? startIndex + 1 : 0) +
            "–" + Math.min(startIndex + pageSize, totalStudents) +
            " of " + totalStudents + " students";

        pageInfo.textContent =
            "Page " + currentPage + " of " + totalPages;

        previousButton.disabled = currentPage <= 1;
        nextButton.disabled = currentPage >= totalPages;
    }

    searchInput.addEventListener("input", function () {
        currentPage = 1;
        renderStudents();
    });

    courseFilter.addEventListener("change", function () {
        currentPage = 1;
        renderStudents();
    });

    resetButton.addEventListener("click", function () {
        searchInput.value = "";
        courseFilter.value = "all";
        currentPage = 1;
        message.textContent = "";
        renderStudents();
        searchInput.focus();
    });

    previousButton.addEventListener("click", function () {
        if (currentPage > 1) {
            currentPage--;
            renderStudents();
        }
    });

    nextButton.addEventListener("click", function () {
        const totalPages = Math.max(
            1,
            Math.ceil(getFilteredStudents().length / pageSize)
        );

        if (currentPage < totalPages) {
            currentPage++;
            renderStudents();
        }
    });

    try {
        const response = await fetch("students.json");

        if (!response.ok) {
            throw new Error("Could not load students.json.");
        }

        const data = await response.json();

        if (!data || !Array.isArray(data.students)) {
            throw new Error("The JSON file must contain a students array.");
        }

        students = data.students.filter(function (student) {
            return student &&
                ["id", "name", "email", "course", "semester"].every(
                    function (key) {
                        return student[key] !== undefined &&
                            student[key] !== null;
                    }
                );
        });

        message.textContent = "";
        renderStudents();
    } catch (error) {
        tableBody.replaceChildren();

        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 5;
        cell.textContent =
            "Unable to load student records. Run this page through XAMPP " +
            "and check that students.json is in the same folder.";

        row.appendChild(cell);
        tableBody.appendChild(row);

        resultCount.textContent = "Student data unavailable";
        pageInfo.textContent = "Page 0";
        previousButton.disabled = true;
        nextButton.disabled = true;
        message.textContent = error.message;

        console.error("Student directory error:", error);
    }
});
