@extends('adminpanel')
@section('title') Настройки @stop
@section('css')
<style>
    .line { 
        stroke: steelblue;
        stroke-width: 2;
        fill: none;
    }
    .top-line{
        stroke: red;
        stroke-width: 1;
        fill:none;
    }

    .axis path,
    .axis line {
        fill: none;
        stroke: grey;
        stroke-width: 1;
        shape-rendering: crispEdges;
    }
</style>
@endsection
@section('subcontent')

<div class='col-xs-12'>
    <div class='row'>
        <div class='col-xs-12 form-inline'>
            <form id='mysqlChartForm'>
                <label>Дата начала</label>
                <input class='form-control' name='start_date' type='date'/>
                <input class='form-control' name='start_time' type='time'/>
                <label>Дата конца</label>
                <input class='form-control' name='end_date' type='date'/>
                <input class='form-control' name='end_time' type='time'/>
                <input name='last_update_datetime' type='hidden'/>
                <button type='button' id='refreshMysqlChartBtn' class='form-control'>Обновить</button>
            </form>
        </div>
        <div class='col-xs-12' id='chartWrapper'>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script src="http://d3js.org/d3.v3.min.js"></script>
<script>
    $(document).ready(function () {
        var allData = [];
        var maxAllDataLength = 100;
        var margin = {top: 30, right: 20, bottom: 60, left: 50},
            width = 960 - margin.left - margin.right,
                    height = 300 - margin.top - margin.bottom;
        var x = d3.time.scale().range([0, width]);
            var y = d3.scale.linear().range([height, 0]);
        var valueline = d3.svg.line()
                    .x(function (d) {
                        return x(d.date);
                    })
                    .y(function (d) {
                        return y(d.amount);
                    });
        function drawTopLine(svg,allData,redraw){
            if(allData.length>0){
                var toplineData = [
                    {
                        date: allData[0].date,
                        amount:50
                    },
                    {
                        date: allData[allData.length-1].date,
                        amount:50
                    }
                ];
                if(redraw){
                    svg.select('.top-line')
                        .attr("stroke", "blue")
                        .attr("stroke-width", 2)
                        .attr("fill", "none")
                        .duration(0)
                        .attr('d',valueline(toplineData));
                } else {
                    svg.append("path")
                        .attr("class", "top-line")
                        .attr("stroke", "blue")
                        .attr("stroke-width", 2)
                        .attr("fill", "none")
                        .attr("d", valueline(toplineData));
                }
            }
        };
        $('#refreshMysqlChartBtn').click(function () {
            $('#chartWrapper svg').remove();
            // Set the dimensions of the canvas / graph
            
            var amountFn = function (d) {
                return d.amount;
            };
            var dateFn = function (d) {
                return parseDate(d.date);
            };
            var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;
            var xAxis = d3.svg.axis().scale(x).orient("bottom").ticks(5).tickFormat(d3.time.format('%H:%M:%S'));

            var yAxis = d3.svg.axis().scale(y).orient("left").ticks(10);
            var svg = d3.select("#chartWrapper")
                    .append("svg")
                    .attr("width", width + margin.left + margin.right)
                    .attr("height", height + margin.top + margin.bottom)
                    .append("g")
                    .attr("transform",
                            "translate(" + margin.left + "," + margin.top + ")");
                            
            $.post($.app.url + '/adminpanel/config/mysql/threads/data', $('#mysqlChartForm').serialize()).done(function (data) {
                data.forEach(function (d) {
                    d.date = parseDate(d.created_at);
                    d.amount = +d.amount;
                });

                allData = data;

                if (allData.length > 0) {
                    $('#mysqlChartForm [name="last_update_datetime"]').val(moment(allData[allData.length - 1].created_at).format('YYYY-MM-DD HH:mm:ss'));
                }

                // Scale the range of the data
                x.domain(d3.extent(data, function (d) {
                    return d.date;
                }));
                y.domain([0, 150]);
                
//
//                // Add the valueline path.
                svg.append("path")
                        .attr("class", "line")
                        .attr("d", valueline(data));
                
                drawTopLine(svg,allData);

                // Add the X Axis
                svg.append("g")
                        .attr("class", "x axis")
                        .attr("transform", "translate(0 ," + height + ")")
                        .call(xAxis)
                        .selectAll('text')
                        .attr("dy", ".35em");

                // Add the Y Axis
                svg.append("g").attr("class", "y axis").call(yAxis);


                setTimeout(function () {
                    if (formIsEmpty()) {
                        updateData();
                    }
                }, 5000);
            });

            var formIsEmpty = function () {
                var allEmpty = true;
                $('#mysqlChartForm input:not([name="last_update_datetime"])').each(function () {
                    if ($(this).val() != '') {
                        allEmpty = false;
                    }
                });
                return allEmpty;
            };
            
            

            var updateData = function () {
                $.post($.app.url + '/adminpanel/config/mysql/threads/data', $('#mysqlChartForm').serialize()).done(function (data) {
                    data.forEach(function (d) {
                        d.date = parseDate(d.created_at);
                        d.amount = +d.amount;
                    });
                    allData = allData.concat(data);
                    if (allData.length > maxAllDataLength) {
                        allData.splice(0, Math.abs(maxAllDataLength - allData.length));
                    }
                    // Scale the range of the data again 
                    x.domain(d3.extent(allData, function (d) {
                        return d.date;
                    }));
//                    y.domain([0, d3.max(allData, function (d) {
//                            return d.amount;
//                        })]);

                    // Select the section we want to apply our changes to
                    var svg = d3.select("body").transition();

                    // Make the changes
                    svg.select(".line")   // change the line
                            .duration(0)
                            .attr("d", valueline(allData));
                    
                    drawTopLine(svg,allData,true);
                    
                    svg.select(".x.axis") // change the x axis
                            .duration(100)
                            .call(xAxis);
                    svg.select(".y.axis") // change the y axis
                            .duration(100)
                            .call(yAxis);


                    if (allData.length > 0) {
                        $('#mysqlChartForm [name="last_update_datetime"]').val(moment(allData[allData.length - 1].created_at).format('YYYY-MM-DD HH:mm:ss'));
                    }

                    if (formIsEmpty()) {
                        setTimeout(function () {
                            updateData();
                        }, 5000);
                    }
                });
            }
        });
    });
</script>
@stop