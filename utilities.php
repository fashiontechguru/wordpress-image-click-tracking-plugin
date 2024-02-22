<?php

function count_total_records() {
    $table_name = IssueMatrices::table_name();
    $total_records = wp_count_rows( $table_name );
    return $total_records;
}