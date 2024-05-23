/* jshint esversion: 6, -W097, -W031 */
/* globals Chart */
"use strict";

$(function() {
    // Default color scheme
    Chart.defaults.global.plugins.colorschemes.scheme = 'tableau.Classic20';

    $("canvas.by-month").each( function() {
        var type = 'bar';
        new Chart( $(this), {
            type: type,
            data: {
                labels: $(this).data('labels'),
                datasets: [{
                    label: 'temps consacré',
                    data: $(this).data('values'),
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        position: 'bottom',
                        ticks: {
                            autoSkip: true,
                            maxRotation: 90
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const y = parseInt(tooltipItem.value);
                            if (y === null) {
                                return '';
                            }
                            if (y === 0) {
                                return '-';
                            }
                            if (y < 60) {
                                return ` ${context.parsed.y} minutes`;
                            }
                            const hours = Math.floor(y / 60);
                            const minutes = y - 60 * hours;
                            return ` ${hours} heures ${minutes} minutes`;
                        }
                    }
                }
            }
        });
    });

    $("canvas[id*='barchart']").each( function() {
        const type = this.id.substring(0, 8) === 'barchart' ? 'bar' : 'horizontalBar';
        new Chart( $(this), {
            type: type,
            data: {
                labels: $(this).data('labels'),
                datasets: [{
                    label: '# of issues',
                    data: $(this).data('values'),
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        position: type === 'bar' ? 'bottom' : 'top',
                        ticks: {
                            autoSkip: false,
                            maxRotation: 90
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    });

    $("canvas[id^='piechart']").each( function() {
        new Chart( $(this), {
            type: 'pie',
            data: {
                labels: $(this).data('labels'),
                datasets: [{
                    label: '# of issues',
                    data:  $(this).data('values'),
                    backgroundColor: $(this).data('colors'),
                    borderColor: $(this).data('colors'),
                    borderWidth: 1
                }]
            }
        });
    });

    $("canvas[id^='linebydate']").each( function() {
        const ctx = $(this).get(0).getContext("2d");
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: $(this).data('labels'),
                datasets: [
                    {
                        label: $(this).data('opened-label'),
                        data: $(this).data('opened-values')
                    },
                    {
                        label: $(this).data('resolved-label'),
                        data: $(this).data('resolved-values')
                    },
                    {
                        label: $(this).data('still-open-label'),
                        data: $(this).data('still-open-values')
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                plugins: {
                    colorschemes: {
                        scheme: 'brewer.Set1-3',
                        reverse: true,
                        fillAlpha: 0.15
                    }
                }
            }
        });
    });
});
