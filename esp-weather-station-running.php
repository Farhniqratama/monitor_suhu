<!--
  Rui Santos
  Complete project details at https://RandomNerdTutorials.com/cloud-weather-station-esp32-esp8266/

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files.

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.
-->
<?php
include_once('esp-database.php');

// Default readings count or from GET parameter
$readings_count = isset($_GET["readingsCount"]) ? htmlspecialchars(trim($_GET["readingsCount"])) : 20;

// Check for date filters
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : null;
$end_time = isset($_GET['end_time']) ? $_GET['end_time'] : null;

// Fetch latest reading for the hitter displays
$latest_reading = getLastReadings();
$last_reading_temp = $latest_reading["value1"];
$last_reading_humi = $latest_reading["value2"];
$last_reading_time = $latest_reading["reading_time"];

// Get filtered or default data for chart and table
if ($start_time && $end_time) {
    $result = filterReadingsByTimestamp($start_time, $end_time, $readings_count);
} else {
    $result = getAllReadings($readings_count);
}

// Initialize arrays for chart data
$timestamps = [];
$temperatures = [];
$humidities = [];

// Process the data for both table and chart
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $local_time = new DateTime($row['reading_time']);
        $timestamps[] = $local_time->format('d F Y H:i:s');
        $temperatures[] = $row['value1'];
        $humidities[] = $row['value2'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Weather Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- Include Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: url('grey.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            color: #000000FF;
            padding: 30px 20px;
            text-align: center;
            border-radius: 0 0 15px 15px;
            position: relative;
            background: transparent;
            /* Inherit from body for continuity */
            z-index: 1;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Semi-transparent overlay */
            z-index: 1;
            border-radius: 0 0 15px 15px;
        }

        .header h1 {
            position: relative;
            z-index: 2;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .header form {
            position: relative;
            z-index: 2;
        }

        .form-control-lg {
            font-size: 1.25rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
        }

        .btn-lg {
            font-size: 1.25rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: background-color 0.3s ease-in-out, transform 0.2s;
        }

        .btn-lg:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .dashboard {
            margin-top: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
        }

        .chart-container {
            margin-top: 30px;
            background-color: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stats-table {
            margin-top: 30px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: #ffffff;
            /* White background for table */
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
            border-radius: 15px;
            /* Rounded corners for modern look */
            overflow: hidden;
            /* Ensures proper rounding */
        }

        .stats-table th {
            background: linear-gradient(90deg, #0056b3, #007bff);
            /* Gradient background */
            color: white;
            text-transform: uppercase;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 15px;
            /* Increased padding for spacing */
        }

        .table-hover tbody tr {
            transition: background-color 0.3s ease-in-out;
            /* Smooth hover transition */
        }

        .table-hover tbody tr:hover {
            background-color: #f9f9f9;
            /* Light gray hover effect */
        }

        .stats-table td {
            vertical-align: middle;
            font-size: 1rem;
            padding: 15px;
            /* Added padding for better spacing */
            border-top: 1px solid #f1f1f1;
            /* Subtle border between rows */
            text-align: center;
            /* Center align text */
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 5px;
            /* Smooth badge corners */
        }

        .table-responsive {
            margin-top: 30px;
            overflow-x: auto;
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.1);
            /* Shadow for modern look */
            border-radius: 15px;
            /* Matches table styling */
        }

        .hitter-container {
            position: relative;
        }


        .hitter-bar {
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .hitter-fill {
            height: 100%;
            background: linear-gradient(90deg, #0044cc, #0099ff);
            border-radius: 10px;
            /* Animation for gradient */
        }


        .hitter-value {
            position: absolute;
            right: 10px;
            /* Adjusts the value to the right side of the bar */
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            font-weight: bold;
            color: #0044cc;
        }

        .marquee {
            width: 100%;
            max-width: 90%;
            margin: 50px auto;
            padding: 15px;
            background-color: #f5f7fa;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #0044cc;
            overflow: hidden;
            position: relative;
        }

        .marquee-content {
            display: inline-block;
            white-space: nowrap;
            font-size: 1.2em;
            font-weight: 600;
            color: #444;
            animation: marquee 30s linear infinite;
            /* Increased duration for slower scroll */
            padding-left: 100%;
        }

        @keyframes marquee {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(-100%, 0);
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container-md">
            <form method="get" class="row justify-content-center align-items-center p-4 mt-5 mb-5"
                style="border: 1px solid #ccc; border-radius: 12px; background-color: #fff; box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);">
                <div class="col-12 text-center mb-4">
                    <h1 class="mb-0">DEPOT ARSIP</h1>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <input id="readingsCountInput" type="number" name="readingsCount"
                        class="form-control form-control-lg" min="1"
                        placeholder="Enter number of readings (<?php echo $readings_count; ?>)" required>
                </div>
                <div class="col-md-2 col-sm-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100" id="updateButton">Update</button>
                </div>
            </form>
        </div>
    </header>

    <script>
        function validateForm() {
            const inputField = document.getElementById('readingsCountInput');
            if (!inputField.value.trim()) {
                // If the input is empty, show an alert or prevent submission
                alert('Please enter a valid number of readings.');
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
    </script>

    <div class="marquee">
        <div class="marquee-content">
            <span>
                Selamat Datang di Aplikasi Depot Arsip |
                Suhu saat ini :<img src="temperature.png" alt="Temperature Icon"
                    style="width: 20px; vertical-align: middle; margin-left: 5px;" />
                <?php echo $last_reading_temp; ?>°C
                dan
                Kelembapan saat ini:<img src="humidity.png" alt="Humidity Icon"
                    style="width: 20px; vertical-align: middle; margin-left: 5px;" />
                <?php echo $last_reading_humi; ?>%

            </span>
        </div>
    </div>

    <div class="container dashboard">
        <div class="row">
            <!-- Latest Temperature Hitter Display -->
            <div class="col-md-6 mb-4">
                <div class="card text-center p-4 shadow-sm border-0 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <h3 class="mb-0 me-3">Temperature Saat Ini</h3>
                            <img src="temperature.png" alt="Temperature Icon" style="width: 50px;">
                        </div>
                        <div class="hitter-container mt-3">
                            <div class="hitter-bar">
                                <div class="hitter-fill"
                                    style="width: <?php echo ($last_reading_temp / 50) * 100; ?>%;"></div>
                                <span class="hitter-value"><?php echo $last_reading_temp; ?>°C</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary mt-4" style="font-size: 0.9rem;">
                        Last Reading: <?php echo date('d F Y H:i:s', strtotime($last_reading_time)); ?>
                    </div>
                </div>
            </div>

            <!-- Latest Humidity Hitter Display -->
            <div class="col-md-6 mb-4">
                <div class="card text-center p-4 shadow-sm border-0 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <h3 class="mb-0 me-3">Humidity Saat Ini</h3>
                            <img src="humidity.png" alt="Humidity Icon" style="width: 50px;">
                        </div>
                        <div class="hitter-container mt-3">
                            <div class="hitter-bar">
                                <div class="hitter-fill"
                                    style="width: <?php echo ($last_reading_humi / 100) * 100; ?>%;"></div>
                                <span class="hitter-value"><?php echo $last_reading_humi; ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary mt-4" style="font-size: 0.9rem;">
                        Last Reading: <?php echo date('d F Y H:i:s', strtotime($last_reading_time)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Chart -->
        <div class="chart-container mt-5">
            <h2 class="text-center mb-4">Temperature and Humidity</h2>
            <canvas id="readingsChart"></canvas>
        </div>

        <form method="get" class="row justify-content-center align-items-center p-4 mt-5 mb-5"
            style="border: 1px solid #ccc; border-radius: 12px; background-color: #fff; box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);">
            <?php
            // Fetch the latest timestamp from your database
            $latestReadingTimestamp = $latest_reading['reading_time'] ?? date("Y-m-d H:i:s"); // Fallback to current timestamp if no reading exists
            
            // Convert to local timezone
            $dateTime = new DateTime($latestReadingTimestamp, new DateTimeZone('UTC')); // Assuming the timestamp is in UTC
            $dateTime->setTimezone(new DateTimeZone(date_default_timezone_get())); // Set to the server's local timezone
            $latestFormattedTimestamp = $dateTime->format("d-m-Y H:i"); // Format to dd-mm-yyyy and time
            
            // Set values for filtered time inputs
            $filteredStartTime = isset($_GET['start_time']) ? htmlspecialchars($_GET['start_time']) : $latestFormattedTimestamp;
            $filteredEndTime = isset($_GET['end_time']) ? htmlspecialchars($_GET['end_time']) : $latestFormattedTimestamp;
            ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <input type="text" id="start_time" name="start_time" class="form-control form-control-lg"
                    placeholder="Start Time" value="<?php echo $filteredStartTime; ?>" required>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <input type="text" id="end_time" name="end_time" class="form-control form-control-lg"
                    placeholder="End Time" value="<?php echo $filteredEndTime; ?>" required>
            </div>
            <div class="col-md-2 col-sm-4">
                <button type="submit" class="btn btn-primary btn-lg w-100">Filter</button>
            </div>
        </form>

        <!-- Include Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            // Initialize Flatpickr on the input fields
            flatpickr("#start_time", {
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                time_24hr: true
            });

            flatpickr("#end_time", {
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                time_24hr: true
            });
        </script>

        <div class="row mt-4">
            <?php if (!empty($timestamps) && $start_time && $end_time): ?>
                <!-- Filtered Temperature Display -->
                <div class="col-md-6 mb-4">
                    <div class="card text-center p-4 shadow-sm border-0 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <h3 class="mb-0 me-3">Filtered Temperature</h3>
                                <img src="temperature.png" alt="Temperature Icon" style="width: 50px;">
                            </div>
                            <div class="hitter-container mt-3">
                                <div class="hitter-bar">
                                    <div class="hitter-fill"
                                        style="width: <?php echo (max($temperatures) / 50) * 100; ?>%;"></div>
                                    <span
                                        class="hitter-value"><?php echo round(array_sum($temperatures) / count($temperatures), 2); ?>°C</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary mt-4" style="font-size: 0.9rem;">
                            <strong><?php echo date('d F Y H:i', strtotime($start_time)); ?></strong> to
                            <strong><?php echo date('d F Y H:i', strtotime($end_time)); ?></strong>
                            <br>Min: <?php echo min($temperatures); ?>°C | Max: <?php echo max($temperatures); ?>°C
                        </div>
                    </div>
                </div>

                <!-- Filtered Humidity Display -->
                <div class="col-md-6 mb-4">
                    <div class="card text-center p-4 shadow-sm border-0 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <h3 class="mb-0 me-3">Filtered Humidity</h3>
                                <img src="humidity.png" alt="Humidity Icon" style="width: 50px;">
                            </div>
                            <div class="hitter-container mt-3">
                                <div class="hitter-bar">
                                    <div class="hitter-fill" style="width: <?php echo (max($humidities) / 100) * 100; ?>%;">
                                    </div>
                                    <span
                                        class="hitter-value"><?php echo round(array_sum($humidities) / count($humidities), 2); ?>%</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary mt-4" style="font-size: 0.9rem;">
                            <strong><?php echo date('d F Y H:i', strtotime($start_time)); ?></strong> to
                            <strong><?php echo date('d F Y H:i', strtotime($end_time)); ?></strong>
                            <br>Min: <?php echo min($humidities); ?>% | Max: <?php echo max($humidities); ?>%
                        </div>
                    </div>
                </div>
            <?php elseif (empty($timestamps) && $start_time && $end_time): ?>
                <!-- No Data Alert -->
                <div class="col-md-12 text-center">
                    <div class="alert alert-warning" role="alert">
                        No data available for the selected range.
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <h2 class="mt-4">Recent Readings</h2>
        <table class="table table-hover table-striped text-center stats-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sensor</th>
                    <th>Location</th>
                    <th>Temperature</th>
                    <th>Humidity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($timestamps)) {
                    foreach ($result as $row) {
                        echo "<tr>
                    <td>{$row['id']}</td> <!-- Use the actual ID from the database -->
                    <td>{$row['sensor']}</td>
                    <td>{$row['location']}</td> <!-- Fetch and display location dynamically -->
                    <td><span class='badge bg-info text-dark'>{$row['value1']}°C</span></td>
                    <td><span class='badge bg-success'>{$row['value2']}%</span></td>
                    <td>" . date('d F Y H:i:s', strtotime($row['reading_time'])) . "</td>
                </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No data available for the selected range.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        const ctx = document.getElementById('readingsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse($timestamps)); ?>, // Reverse the timestamps array
                datasets: [
                    {
                        label: 'Temperature (°C)',
                        data: <?php echo json_encode(array_reverse($temperatures)); ?>, // Reverse the temperatures array
                        borderColor: '#ff6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ff6384',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Humidity (%)',
                        data: <?php echo json_encode(array_reverse($humidities)); ?>, // Reverse the humidities array
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#36a2eb',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#000',
                        bodyColor: '#000'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Timestamp'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Values'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>