<?php

include "constants.php";

const UPDATE_RECIPE_SQL = "UPDATE " . Constants::RECIPES_TABLE . " SET name = ?, description = ? WHERE recipe_id = ?";
const UPDATE_INGREDIENTS_SQL = "UPDATE " . Constants::RECIPE_INGREDIENTS_TABLE . " SET ingredient = ? WHERE ingredient_id = ?";
const UPDATE_INSTRUCTIONS_SQL = "UPDATE " . Constants::RECIPE_INSTRUCTIONS_TABLE . " SET instruction = ? WHERE instruction_id = ?";

const INSERT_INGREDIENTS_SQL = "INSERT INTO " . Constants::RECIPE_INGREDIENTS_TABLE . " (recipe_id, ingredient) VALUES ";
const INSERT_INSTRUCTIONS_SQL = "INSERT INTO " . Constants::RECIPE_INSTRUCTIONS_TABLE . " (recipe_id, instruction) VALUES ";

// Create connection to sql
$conn = mysqli_connect(Constants::SERVER_NAME, Constants::USER_NAME, Constants::PASSWORD);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} 

// Take the data from the request
$data = file_get_contents('php://input');
$json = json_decode($data, true);

// Grab JSON data
$recipe_name = $json["name"];
$recipe_id = $json["recipe_id"];
$recipe_description = ($json["description"] == "") ? null : $json["description"];
$ingredients = ($json["ingredients"] == "") ? array() : $json["ingredients"];
$instructions = ($json["instructions"] == "") ? array() : $json["instructions"];
$ingredientToIdMap = ($json["ingredientToIdMap"] == "") ? array() : $json["ingredientToIdMap"];
$instructionToIdMap = ($json["instructionToIdMap"] == "") ? array() : $json["instructionToIdMap"];

// Begin transaction
$conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

// Update recipes table with name and description
$update_recipe_sql_ps = $conn->prepare(UPDATE_RECIPE_SQL);
$update_recipe_sql_ps->bind_param("ssi", $recipe_name, $recipe_description, $recipe_id);
if($update_recipe_sql_ps->execute()) {
	echo Constants::SUCCESS_STRING;
}
else {
	die("Error updating recipe: " . $update_recipe_sql_ps->error);
}

// Update ingredients
if(count($ingredients) > 0) {

	// Create array to store all recipe id and ingredient pairs
	$recipe_ingredient_sql_pairs = array();

	// Find any new ingredients by checking that they don't have an associated ID
	foreach($ingredients as $ingredient) {
		if(!array_key_exists($ingredient, $ingredientToIdMap)) {		
			// Add to sql pairs array
			$recipe_ingredient_pair = "($recipe_id, '$ingredient')";
			array_push($recipe_ingredient_sql_pairs, $recipe_ingredient_pair);
		}
	}
	
	// Add sql pairs to insert sql string
	$insert_ingredients_sql = INSERT_INGREDIENTS_SQL . implode(",", $recipe_ingredient_sql_pairs);

	// Insert any new ingredients
	if(count($recipe_ingredient_sql_pairs) > 0) {
		if($conn->query($insert_ingredients_sql)) {
			echo Constants::SUCCESS_STRING;
		}
		else {
			die("Error inserting new ingredients");
		}
	}

	// Update existing ingredients by checking if they have an associated ingredient id
	if(count($ingredientToIdMap) > 0) {
		foreach($ingredientToIdMap as $ingredient => $ingredient_id) {

			// Create prepared statement 
			$update_ingredient_ps = $conn->prepare(UPDATE_INGREDIENTS_SQL);
			$update_ingredient_ps->bind_param("si", $ingredient, $ingredient_id);

			// Update ingredient
			if($update_ingredient_ps->execute()) {
				echo Constants::SUCCESS_STRING;
			}
			else {
				die("Error updating ingredient: " . $update_ingredient_ps->error);
			}
		}
	}  

}

// Update instructions
if(count($instructions) > 0) {

	// Create array to store all recipe id and ingredient pairs
	$recipe_instruction_sql_pairs = array();

	// Find any new instruction by checking that they don't have an associated ID
	foreach($instructions as $instruction) {
		if(!array_key_exists($instruction, $instructionToIdMap)) {		
			// Add to sql pairs
			$recipe_instruction_pair = "($recipe_id, '$instruction')";
			array_push($recipe_instruction_sql_pairs, $recipe_instruction_pair);
		}
	}

	// Add sql pairs to insert instructions string
	$insert_instructions_sql = INSERT_INSTRUCTIONS_SQL . implode(",", $recipe_instruction_sql_pairs);

	// Insert any new instructions
	if(count($recipe_instruction_sql_pairs) > 0) {
		if($conn->query($insert_instructions_sql)) {
			echo Constants::SUCCESS_STRING;
		}
		else {
			die("Error inserting new instructions");
		}
	}

	// Update existing instructions by checking if they have an associated instruction id
	if(count($instructionToIdMap) > 0) {
		foreach($instructionToIdMap as $instruction => $instruction_id) {

			// Create prepared statement
			$update_instruction_ps = $conn->prepare(UPDATE_INSTRUCTIONS_SQL);
			$update_instruction_ps->bind_param("si", $instruction, $instruction_id);

			// Update instruction
			if($update_instruction_ps->execute()) {
				echo Constants::SUCCESS_STRING;
			}
			else {
				die("Error updating instruction: " . $update_instruction_ps->error);
			}
		}
	}  
}



// End transaction
$conn->commit();

// Close the connection
$conn->close();

?>