
document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.getElementById("studentTableBody");
    const searchInput = document.getElementById("studentSearch");
    const courseFilter = document.getElementById("courseFilter");
    const resetButton = document.getElementById("resetFilters");
    const resultCount = document.getElementById("resultCount");
    const pageInfo = document.getElementById("pageInfo");
    const previousButton = document.getElementById("previousPage");
    const nextButton = document.getElementById("nextPage");
    const directoryMessage = document.getElementById("directoryMessage");

    let allStudents = [];
    let currentPage = 1;
    const pageSize = 5;

    // Absolute API path for your StudentHub project.
    const API_URL = "/student/practical-7/students-api.php";

    async function loadStudents() {
        try {
            if (!tableBody) {
                throw new Error("Student table body was not found in students.html.");
            }

            directoryMessage.textContent = "Loading students...";

            const response = await fetch(
                API_URL + "?nocache=" + Date.now(),
                {
                    method: "GET",
                    cache: "no-store",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

            if (!response.ok) {
                throw new Error("API returned HTTP " + response.status);
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.students)) {
                throw new Error("The API response format is incorrect.");
            }

            allStudents = data.students;
            currentPage = 1;

            // Populate the course filter from available student records.
            if (courseFilter) {
                const selectedCourse = courseFilter.value;
                const courses = [...new Set(
                    allStudents
                        .map(student => String(student.course || "").trim())
                        .filter(Boolean)
                )].sort();

                courseFilter.replaceChildren();

                const allOption = document.createElement("option");
                allOption.value = "all";
                allOption.textContent = "All courses";
                courseFilter.appendChild(allOption);

                courses.forEach(course => {
                    const option = document.createElement("option");
                    option.value = course.toLowerCase();
                    option.textContent = course;
                    courseFilter.appendChild(option);
                });

                if ([...courseFilter.options].some(
                    option => option.value === selectedCourse
                )) {
                    courseFilter.value = selectedCourse;
                }
            }

            renderStudents();
        } catch (error) {
            console.error("Student directory error:", error);

            if (tableBody) {
                tableBody.replaceChildren();

                const row = document.createElement("tr");
                const cell = document.createElement("td");

                cell.colSpan = 5;
                cell.textContent = "Unable to load student records.";

                row.appendChild(cell);
                tableBody.appendChild(row);
            }

            if (resultCount) {
                resultCount.textContent = "Student loading failed";
            }

            if (directoryMessage) {
                directoryMessage.textContent =
                    error.message || "Unable to connect to the Student API.";
            }
        }
    }

    function renderStudents() {
        if (!tableBody) return;

        const search = searchInput
            ? searchInput.value.trim().toLowerCase()
            : "";

        const course = courseFilter
            ? courseFilter.value.toLowerCase()
            : "all";

        const filtered = allStudents.filter(function (student) {
            const matchesSearch = [
                student.studentId,
                student.name,
                student.email
            ].some(function (value) {
                return String(value || "").toLowerCase().includes(search);
            });

            const matchesCourse =
                course === "all" ||
                String(student.course || "").toLowerCase() === course;

            return matchesSearch && matchesCourse;
        });

        const totalPages = Math.max(
            1,
            Math.ceil(filtered.length / pageSize)
        );

        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        const start = (currentPage - 1) * pageSize;
        const visibleStudents = filtered.slice(start, start + pageSize);

        tableBody.replaceChildren();

        if (visibleStudents.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");

            cell.colSpan = 5;
            cell.textContent = "No matching students found.";

            row.appendChild(cell);
            tableBody.appendChild(row);
        } else {
            visibleStudents.forEach(function (student) {
                const row = document.createElement("tr");

                [
                    student.studentId,
                    student.name,
                    student.email,
                    student.course,
                    student.semester
                ].forEach(function (value) {
                    const cell = document.createElement("td");
                    cell.textContent = value || "—";
                    row.appendChild(cell);
                });

                tableBody.appendChild(row);
            });
        }

        if (resultCount) {
            resultCount.textContent =
                "Showing " + visibleStudents.length +
                " of " + filtered.length + " students";
        }

        if (pageInfo) {
            pageInfo.textContent =
                "Page " + currentPage + " of " + totalPages;
        }

        if (previousButton) {
            previousButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }

        if (directoryMessage) {
            directoryMessage.textContent = "";
        }
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            currentPage = 1;
            renderStudents();
        });
    }

    if (courseFilter) {
        courseFilter.addEventListener("change", function () {
            currentPage = 1;
            renderStudents();
        });
    }

    if (resetButton) {
        resetButton.addEventListener("click", function () {
            if (searchInput) searchInput.value = "";
            if (courseFilter) courseFilter.value = "all";

            currentPage = 1;
            renderStudents();
        });
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage--;
                renderStudents();
            }
        });
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            currentPage++;
            renderStudents();
        });
    }

    loadStudents();
});
