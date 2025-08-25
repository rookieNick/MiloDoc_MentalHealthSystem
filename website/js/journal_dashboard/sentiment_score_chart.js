

// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#292b2c';





// Extracting labels (dates) and scores from JSON
var labels = scoreData.map(item => item.report_date);
var scores = scoreData.map(item => item.sentiment_score);



// Area Chart Example
var ctx = document.getElementById("sentiment_score_chart");
var myLineChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: "Mental Health Score",
      lineTension: 0.2,
      // backgroundColor: "rgba(2,117,216,0.2)",
      backgroundColor: "rgba(2, 116, 216, 0)",
      borderColor: "rgba(106, 46, 255,1)",
      pointRadius: 5,
      pointBackgroundColor: "rgb(106, 46, 255)",
      pointBorderColor: "rgba(255,255,255,0.8)",
      pointHoverRadius: 5,
      pointHoverBackgroundColor: "rgba(106, 46, 255,1)",
      pointHitRadius: 50,
      pointBorderWidth: 2,
      data: scores,
    }],
  },
  options: {

    scales: {
      xAxes: [{
        time: {
          unit: 'date'
        },
        gridLines: {
          display: false
        },
        ticks: {
          maxTicksLimit: 3
        }
      }],
      yAxes: [{
        ticks: {
          min: 0,
          max: 100,
          maxTicksLimit: 5
        },
        gridLines: {
          color: "rgba(0, 0, 0, .125)",
        }
      }],
    },
    legend: {
      display: false
    },

  }
});